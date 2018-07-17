<section class="dashboard__block">
    <div class="dashboard__block__content">
        <h4>@partials('partials.wiki_title')</h4>
        <p>@partials('partials.wiki_text')</p>
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
    </div>
</section>
