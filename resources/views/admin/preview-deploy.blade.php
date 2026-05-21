@extends('layouts.app')

@section('title')
Deploy Preview Branch
@endsection

@section('content')
<section>
    <div class="container mt-4">
        <h1>Deploy Preview Branch to restarters.dev</h1>
        <p class="text-muted">Select a branch or open PR to deploy to the develop container. The container will rebuild and restore the latest overnight database backup (~15 minutes total).</p>

        @if (session('success'))
            <div class="alert alert-success">
                {!! session('success') !!}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $e)
                    <p class="mb-0">{{ $e }}</p>
                @endforeach
            </div>
        @endif

        @if ($error)
            <div class="alert alert-warning">{{ $error }}</div>
        @endif

        <form method="POST" action="{{ route('admin.preview-deploy.deploy') }}">
            @csrf

            <div class="form-group">
                <label for="branch"><strong>Branch to deploy</strong></label>
                <select name="branch" id="branch" class="form-control" style="max-width: 500px;">
                    <optgroup label="Main branches">
                        <option value="develop">develop</option>
                        <option value="master">master</option>
                    </optgroup>
                    @if (count($prs))
                        <optgroup label="Open pull requests">
                            @foreach ($prs as $pr)
                                <option value="{{ $pr['branch'] }}">
                                    #{{ $pr['number'] }} {{ $pr['title'] }} ({{ $pr['branch'] }})
                                </option>
                            @endforeach
                        </optgroup>
                    @endif
                </select>
            </div>

            <button type="submit" class="btn btn-primary mt-2"
                    onclick="return confirm('This will redeploy restarters.dev with the selected branch and restore the overnight database. Continue?')">
                Deploy to restarters.dev
            </button>
        </form>

        <hr class="mt-4">
        <p class="text-muted small">
            Deploys are queued as GitHub Actions runs.
            <a href="https://github.com/{{ env('GITHUB_REPO', 'TheRestartProject/restarters.net') }}/actions/workflows/preview-deploy.yml" target="_blank">
                View running workflows →
            </a>
        </p>
    </div>
</section>
@endsection
