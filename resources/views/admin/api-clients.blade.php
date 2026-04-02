@extends('layouts.app')

@section('content')
<section class="admin">
  <div class="container">
      @if (\Session::has('success'))
          <div class="alert alert-success">
              {!! \Session::get('success') !!}
          </div>
      @endif

      @if (\Session::has('danger'))
          <div class="alert alert-danger">
              {!! \Session::get('danger') !!}
          </div>
      @endif

      @if ($errors->any())
          <div class="alert alert-danger">
              <ul class="mb-0">
                  @foreach ($errors->all() as $error)
                      <li>{{ $error }}</li>
                  @endforeach
              </ul>
          </div>
      @endif

      @if (\Session::has('generated_api_token'))
          @php($generatedToken = \Session::get('generated_api_token'))
          <div class="alert alert-warning">
              <h2 class="h5">New token generated for {{ $generatedToken['name'] }}</h2>
              <p class="mb-2">This token is shown once. Store it now. If it is lost, rotate the client to mint a new token.</p>
              <p class="mb-1"><strong>Token ID:</strong> <code>{{ $generatedToken['token_hint'] }}</code></p>
              <p class="mb-0"><strong>Token:</strong> <code>{{ $generatedToken['token'] }}</code></p>
          </div>
      @endif

      <div class="row mb-30">
          <div class="col-12">
              <div class="d-flex align-items-center">
                  <h1 class="mb-0">API clients</h1>
              </div>
              <p class="mt-3">Create and manage bearer-token clients for the public events API. Only the masked token ID is stored after creation.</p>
          </div>
      </div>

      <div class="row mb-4">
          <div class="col-12">
              <div class="card">
                  <div class="card-body">
                      <h2 class="h4">Create API client</h2>
                      <form method="POST" action="{{ route('admin.api-clients.store') }}">
                          @csrf
                          <div class="form-group">
                              <label for="name">Name</label>
                              <input id="name" name="name" class="form-control" value="{{ old('name') }}" required>
                          </div>

                          <div class="form-group">
                              <label for="allowed_origins">Allowed origins</label>
                              <textarea id="allowed_origins" name="allowed_origins" class="form-control" rows="3" placeholder="https://example.org, https://subdomain.example.org">{{ old('allowed_origins') }}</textarea>
                              <small class="form-text text-muted">Leave blank to allow any origin. Separate multiple origins with commas or new lines.</small>
                          </div>

                          <div class="form-group">
                              <label for="allowed_network_ids">Allowed networks</label>
                              <select id="allowed_network_ids" name="allowed_network_ids[]" class="form-control" multiple size="6">
                                  @foreach ($networks as $network)
                                      <option value="{{ $network->id }}" @selected(in_array($network->id, old('allowed_network_ids', [])))>{{ $network->name }}</option>
                                  @endforeach
                              </select>
                              <small class="form-text text-muted">Leave empty to allow all networks.</small>
                          </div>

                          <div class="form-group">
                              <label for="rate_limit_per_minute">Rate limit per minute</label>
                              <input id="rate_limit_per_minute" name="rate_limit_per_minute" type="number" min="1" class="form-control" value="{{ old('rate_limit_per_minute', 120) }}" required>
                          </div>

                          <div class="form-group">
                              <label for="expires_at">Expires at</label>
                              <input id="expires_at" name="expires_at" type="datetime-local" class="form-control" value="{{ old('expires_at') }}">
                              <small class="form-text text-muted">Leave blank for a non-expiring token.</small>
                          </div>

                          <div class="form-group mb-0">
                              <label>Scope</label>
                              <input class="form-control" value="events:read" readonly>
                          </div>

                          <button type="submit" class="btn btn-primary mt-3">Create client</button>
                      </form>
                  </div>
              </div>
          </div>
      </div>

      <div class="row">
          <div class="col-12">
              <div class="table-responsive table-section">
                  <table class="table table-hover table-striped">
                      <thead>
                          <tr>
                              <th>Name</th>
                              <th>Status</th>
                              <th>Token ID</th>
                              <th>Networks</th>
                              <th>Allowed origins</th>
                              <th>Rate</th>
                              <th>Expires</th>
                              <th>Last used</th>
                              <th>Actions</th>
                          </tr>
                      </thead>
                      <tbody>
                          @forelse ($clients as $client)
                              <tr>
                                  <td>
                                      <strong>{{ $client->name }}</strong><br>
                                      <small class="text-muted">{{ implode(', ', $client->scopes ?: []) }}</small>
                                  </td>
                                  <td>{{ $client->active ? 'Active' : 'Revoked' }}</td>
                                  <td><code>{{ $client->token_hint }}</code></td>
                                  <td>
                                      @if (!empty($client->allowed_network_names))
                                          {{ implode(', ', $client->allowed_network_names) }}
                                      @else
                                          All networks
                                      @endif
                                  </td>
                                  <td>
                                      @if (!empty($client->allowed_origins))
                                          {{ implode(', ', $client->allowed_origins) }}
                                      @else
                                          All origins
                                      @endif
                                  </td>
                                  <td>{{ $client->rate_limit_per_minute }}/min</td>
                                  <td>{{ $client->expires_at ? $client->expires_at->toDayDateTimeString() : 'Never' }}</td>
                                  <td>{{ $client->last_used_at ? $client->last_used_at->toDayDateTimeString() : 'Never' }}</td>
                                  <td class="d-flex">
                                      <form method="POST" action="{{ route('admin.api-clients.rotate', $client->id) }}" class="mr-2">
                                          @csrf
                                          <button type="submit" class="btn btn-sm btn-outline-primary">Rotate</button>
                                      </form>

                                      @if ($client->active)
                                          <form method="POST" action="{{ route('admin.api-clients.revoke', $client->id) }}">
                                              @csrf
                                              <button type="submit" class="btn btn-sm btn-danger">Revoke</button>
                                          </form>
                                      @endif
                                  </td>
                              </tr>
                          @empty
                              <tr>
                                  <td colspan="9">No API clients created yet.</td>
                              </tr>
                          @endforelse
                      </tbody>
                  </table>
              </div>
          </div>
      </div>
  </div>
</section>
@endsection
