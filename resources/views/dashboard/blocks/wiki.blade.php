<section class="dashboard__block">
    <div class="dashboard__block__header dashboard__block__header--wiki">
        <h4>@lang('partials.wiki_title')</h4>
    </div>
    <div class="dashboard__block__content">
        <p>@lang('partials.wiki_text')</p>
        <div class="table-responsive">
            <table role="table" class="table table-striped">
                <tbody>
                    @foreach ($wiki_pages as $wiki_page)
                        <tr>
                            <td><a href="{{ env('WIKI_URL') }}/{{ $wiki_page->title }}" target="_blank" rel="noopener noreferrer">{{ $wiki_page->title }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="dashboard__links d-flex flex-row justify-content-end">
            <a href="https://therestartproject.org/blog/" target="_blank" rel="noopener noreferrer">@lang('dashboard.visit_wiki')</a>
        </div>
    </div>
</section>
