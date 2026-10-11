#!/usr/bin/env python3
"""
flowmine fitness demo (prototype of the PHP artisan pipeline).

Input : events.jsonl  (simulated capture, derived from real UI code)
Reads : the REAL Playwright specs in tests/Integration/ to build the manifest
Output: per-flow verdict — MATCHED(spec, evidence) or UNCOVERED(generated spec)
"""
import json, re, sys, glob, collections, os

REPO = __import__('os').path.join(__import__('os').path.dirname(__file__), '..', '..')
EVENTS = os.path.join(os.path.dirname(__file__), 'events.jsonl')

# ── mining-time noise controls (from the design) ────────────────────────────
AMBIENT_ROUTES = {
    # seeded from the mounted()-hook audit
    'GET api/timezones', 'GET api/v2/groups/names',
    'GET api/users/{id}/notifications', 'GET api/v2/groups/summary',
    'GET api/v2/networks', 'GET api/v2/groups/tags',
}

def sessionize():
    sessions = collections.defaultdict(list)
    for line in open(EVENTS):
        e = json.loads(line)
        sessions[e['session']].append(e)
    for s in sessions.values():
        s.sort(key=lambda e: e['ts'])
    return sessions

def flow_signature(events):
    """Canonical steps: page views, non-ambient action routes, interaction targets.
       A flow ends at the first state-changing API response; the redirect page_view
       that follows it is included as the terminal step (segmentation rule)."""
    steps, pages, routes, targets = [], [], set(), set()
    ended = False
    for e in events:
        if ended and not e.get('redirect_of'):
            break
        if e['type'] == 'page_view':
            pages.append(e['page'])
            steps.append(('page', e['page']))
            if e.get('redirect_of'):
                break
        elif e['type'] in ('click', 'change'):
            t = normalize_target(e['target'])
            targets.add(t)
            steps.append((e['type'], t))
        elif e['type'] == 'api':
            if e['route'] in AMBIENT_ROUTES:
                continue                      # ambient-route denylist
            routes.add(e['route'])
            steps.append(('api', e['route']))
            if e.get('state_changing'):
                ended = True
    return {'pages': pages, 'routes': routes, 'targets': targets, 'steps': steps}

def normalize_target(t):
    """Variant normalization: strip the discovery: prefix, drop per-instance noise."""
    t = t.replace('discovery:', '')
    t = re.sub(r'\[id\^=.*?\]', '', t)
    return t

def variant_key(sig):
    """Design's field-group abstraction: a variant is defined by its dominant
       flow page and its action-route set; optional-field presence/order is an
       annotation on exemplars, not a new variant."""
    flow_page = sig['pages'][0]
    for p in sig['pages']:
        if p not in ('dashboard.index',):
            flow_page = p           # last non-terminal page wins as the flow home
    core = [p for p in sig['pages'] if not p.endswith('.index')]
    flow_page = core[0] if core else sig['pages'][0]
    return (flow_page, frozenset(sig['routes']))

# ── manifest side: read the REAL specs ──────────────────────────────────────
def extract_spec_manifest():
    manifest = {}
    helper_effects = {
        # utils.js helpers resolved once (the real extractor parses utils.js too)
        'login': {'pages': ['/login'], 'selectors': {'#fp_email', '#password', 'button[type=submit]'}, 'routes': set()},
        'createGroup': {'pages': ['/group', '/group/create'],
                        'selectors': {'#group_name', '.ql-editor', '.timezone',
                                      '[placeholder=Enter your address]', 'button(text=Create group)'},
                        'routes': {'POST api/v2/groups'}},
        'unfollowGroup': {'pages': ['/group/view/{id}'], 'selectors': {'#groupactions', '.dropdown-item'}, 'routes': set()},
    }
    for spec in glob.glob(f'{REPO}/tests/Integration/*.test.js'):
        src = open(spec).read()
        name = os.path.basename(spec)
        pages = re.findall(r"page\.goto\(['\"`]([^'\"`]+)", src)
        pages = [re.sub(r"\$\{?\w+\}?|'\s*\+.*", '{id}', p.replace("' + baseURL + '", '')) for p in pages]
        selectors = set(re.findall(r"page\.(?:locator|click|fill|waitForSelector)\(['\"`]([^'\"`]+)['\"`]", src))
        selectors |= {f'(text={t})' for t in re.findall(r"hasText:\s*['\"`]([^'\"`]+)", src)}
        routes = set()
        for rx, method in re.findall(r"waitForResponse\(\s*resp\s*=>\s*/(.*?)/\.test\(resp\.url\(\)\)[^)]*?method\(\)\s*===\s*['\"](\w+)['\"]", src, re.S):
            routes.add(f"{method} {rx.replace(chr(92)+'/', '/').replace(chr(92)+'d+', '{id}').strip('/')}")
        titles = re.findall(r"test\(['\"`]([^'\"`]+)", src)
        used_helpers = [h for h in helper_effects if re.search(rf'\b{h}\(', src)]
        helper_pages, helper_selectors, helper_routes = [], set(), set()
        for h in used_helpers:
            helper_pages += helper_effects[h]['pages']
            helper_selectors |= helper_effects[h]['selectors']
            helper_routes |= helper_effects[h]['routes']
        # evaluate() blocks containing .click(): flag as unextractable but note text hints
        evals = re.findall(r"page\.evaluate\((.*?)\)\s*\n", src, re.S)
        eval_click_texts = set()
        for ev in evals:
            if '.click()' in ev:
                eval_click_texts |= {f'(text={t})' for t in re.findall(r"includes\(['\"]([^'\"]+)['\"]\)", ev)}
        selectors |= eval_click_texts
        manifest[name] = {'pages': set(pages), 'selectors': selectors, 'routes': routes,
                          'helper_pages': set(helper_pages), 'helper_selectors': helper_selectors,
                          'helper_routes': helper_routes, 'titles': titles,
                          'unextractable_eval_clicks': bool(eval_click_texts)}
    return manifest

# ── matching ────────────────────────────────────────────────────────────────
def sel_tokens(s):
    return set(re.findall(r'[#.]?[\w-]+|\(text=[^)]+\)', s))

def match(sig, manifest):
    PAGE_URL = {'group.index': '/group', 'group.create': '/group/create',
                'group.edit': '/group/edit/{id}', 'user.register': '/user/register',
                'dashboard.index': '/dashboard'}
    urlset = {PAGE_URL.get(p, p) for p in sig['pages']}
    flow_tokens = set()
    for p in sig['pages']:
        flow_tokens |= set(p.split('.'))
    best, best_score, best_ev = None, 0.0, None
    for spec, m in manifest.items():
        # Scaffolding rule (design: "domain noun" disambiguation): helper-derived
        # evidence counts fully ONLY if a test title in this spec shares the
        # flow's domain tokens; otherwise it is scaffolding, weighted 0.25.
        title_tokens = set(re.findall(r'[a-z]+', ' '.join(m['titles']).lower()))
        subject = bool(flow_tokens & title_tokens & {'group', 'register', 'event',
                                                     'device', 'user', 'create', 'edit'}
                       and flow_tokens & title_tokens - {'create', 'edit', 'index'})
        w = 1.0 if subject else 0.25
        pages = m['pages'] | m['helper_pages']
        eff_routes = set(m['routes']) | {r for r in m['helper_routes']}
        route_hits = sum((1.0 if r in m['routes'] else w) for r in (sig['routes'] & eff_routes))
        route_overlap = route_hits / max(len(sig['routes']), 1)
        page_hits = sum((1.0 if u in m['pages'] else w) for u in (urlset & pages))
        page_overlap = page_hits / max(len(urlset - {'/dashboard'}), 1)
        flat_targets = set().union(*(sel_tokens(t) for t in sig['targets'])) if sig['targets'] else set()
        own_sel = set().union(*(sel_tokens(s) for s in m['selectors'])) if m['selectors'] else set()
        helper_sel = set().union(*(sel_tokens(s) for s in m['helper_selectors'])) if m['helper_selectors'] else set()
        sel_hits = sum((1.0 if t in own_sel else w) for t in (flat_targets & (own_sel | helper_sel)))
        sel_overlap = sel_hits / max(len(flat_targets), 1)
        score = min(1.0, 0.4 * route_overlap + 0.35 * min(page_overlap, 1.0) + 0.25 * min(sel_overlap, 1.0))
        if score > best_score:
            best, best_score = spec, score
            best_ev = {'subject_match': subject,
                       'page_overlap': round(min(page_overlap, 1.0), 2),
                       'route_overlap': round(route_overlap, 2),
                       'selector_overlap': round(min(sel_overlap, 1.0), 2),
                       'shared_routes': sorted(sig['routes'] & eff_routes),
                       'titles': m['titles'][:3]}
    return (best, best_score, best_ev)

THRESHOLD = 0.60

def main():
    sessions = sessionize()
    manifest = extract_spec_manifest()
    variants = collections.defaultdict(list)
    sigs = {}
    for sid, events in sessions.items():
        sig = flow_signature(events)
        sigs[sid] = sig
        variants[variant_key(sig)].append(sid)

    print(f"Sessions: {len(sessions)}  →  flow variants after normalization: {len(variants)}\n")
    for i, (vk, sids) in enumerate(sorted(variants.items(), key=lambda kv: -len(kv[1])), 1):
        sig = sigs[sids[0]]
        name_guess = f"{sig['pages'][0]}" if sig['pages'] else 'unknown'
        print(f"── Variant {i}: entry page '{name_guess}', {len(sids)} session(s): {sids}")
        print(f"   action routes: {sorted(sig['routes'])}")
        spec, score, ev = match(sig, manifest)
        if score >= THRESHOLD:
            print(f"   VERDICT: MATCHED → {spec}  (score {score:.2f} ≥ {THRESHOLD})")
            print(f"   evidence: {json.dumps(ev)}")
            print(f"   ACTION: no test generated — duplicate avoided.\n")
        else:
            print(f"   VERDICT: UNCOVERED (best candidate {spec} scored {score:.2f} < {THRESHOLD})")
            print(f"   nearest-miss evidence: {json.dumps(ev)}")
            print(f"   ACTION: generate Playwright spec from steps:")
            for kind, val in sig['steps']:
                print(f"     {kind:6} {val}")
            print()

if __name__ == '__main__':
    main()
