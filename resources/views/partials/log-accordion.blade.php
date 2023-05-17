<div class="accordion" id="accordion-log">

  @forelse ($audits as $audit)
      <div class="card">
          <div class="card-header p-0" id="heading{{{ $audit->id }}}">
            <h5 class="mb-0">
              <button class="btn btn-link text-left" type="button" data-toggle="collapse" data-target="#collapse{{{ $audit->id }}}" aria-expanded="true" aria-controls="collapse{{{ $audit->id }}}">
                  @lang($type.'.'.$audit->event.'.metadata', $audit->getMetadata())
              </button>
            </h5>
          </div>
          <div id="collapse{{{ $audit->id }}}" class="collapse" aria-labelledby="heading{{{ $audit->id }}}" data-parent="#accordion-log">
              <div class="card-body">
                  <table class="table table-striped">
                    <tbody>
                      @foreach ($audit->getModified() as $attribute => $modified)
                          <tr>
                            {{-- Some updated data is an array. --}}
                            @php($modified['new'] = is_array($modified['new']) ? json_encode($modified['new']) : $modified['new'])
                            @if(gettype($modified) == 'string')
                            <td>@lang($type.'.'.$audit->event.'.modified.'.$attribute, $modified)</td>
                            @else
                            <td><?php echo $type.'.'.$audit->event.'.modified.'.$attribute . " " . json_encode($modified) ?></td>
                            @endif
                          </tr>
                      @endforeach
                    </tbody>
                  </table>
              </div>
          </div>
      </div>
  @empty
      <div class="text-center">@lang($type.'.unavailable_audits')</div>
  @endforelse

</div>
