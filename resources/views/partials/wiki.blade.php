<section class="dashboard__block">
    <div class="dashboard__block__content">
        <h4>Wiki</h4>
        <p>A changing selection of pages from our repair wiki.  Read and contribute advice for community repair!</p>
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
