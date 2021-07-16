@extends('layouts.app', ['show_navbar_to_anons' => false, 'show_login_join_to_anons' => true, 'hide_language' => true])

@section('title')
Style Guide
@endsection

@section('content')

<style>
    .hide {
        display: none;
    }

    .panel-foo {
        border: 1px dotted #555;
        padding: 5px;
        margin: 5px 0;
    }

    pre {
        font-size: x-small;
    }
</style>

<section>

    <div class="container">
        <h1 class="text-left">Style Guide</h1>
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link nav-link-foo active" data-toggle="overview" href="#overview">Overview</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-foo" data-toggle="text" href="#text">Text</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-foo" data-toggle="panels" href="#panels">Panels</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-foo" data-toggle="cards" href="#cards">Cards</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-foo" data-toggle="badges" href="#badges">Badges</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-foo" data-toggle="alerts" href="#alerts">Alerts</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-foo" data-toggle="buttons" href="#buttons">Buttons</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-foo" data-toggle="inputs" href="#inputs">Inputs</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-foo" data-toggle="tables" href="#tables">Tables</a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-foo" data-toggle="images" href="#images">Images</a>
            </li>
        </ul>
    </div>

    <!-- overview -->
    <div id="overview" class="container">
        <div class="panel-foo">
            <p><a href="https://api.jquery.com/category/version/3.3/" target="_blank">jQuery v3.3.1</a></p>
        </div>
        <div class="panel-foo">
            <p><a href="https://getbootstrap.com/docs/4.1/getting-started/introduction/" target="_blank">Bootstrap v4.1.0</a></p>
            <ul>
                <li><a href="https://getbootstrap.com/docs/4.1/components/alerts" target="_blank">Components</a></li>
                <li><a href="https://getbootstrap.com/docs/4.1/content/typography/" target="_blank">Typography</a></li>
                <li><a href="https://getbootstrap.com/docs/4.1/content/images/" target="_blank">Images</a></li>
                <li><a href="https://getbootstrap.com/docs/4.1/utilities/text/" target="_blank">Text</a></li>
                <li><a href="https://getbootstrap.com/docs/4.1/utilities/colors/" target="_blank">Colors</a></li>
            </ul>
        </div>
        <div class="panel-foo">
            <p><a href="/test/styles/find">Usage</a></p>
        </div>
    </div>

    <!-- text -->
    <div id="text" class="container hide">
        <div class="panel-foo">
            See <a href="https://getbootstrap.com/docs/4.1/utilities/text/" target="_blank">Bootstrap Text</a>
            and <a href="https://getbootstrap.com/docs/4.1/content/typography/" target="_blank">Bootstrap Typography</a>
        </div>
        <div class="panel-foo">
            <h1>h1&nbsp;<a href="#">link</a></h1>
            <h2>h2&nbsp;<a href="#">link</a></h2>
            <h3>h3&nbsp;<a href="#">link</a></h3>
            <h4>h4&nbsp;<a href="#">link</a></h4>
            <h5>h5&nbsp;<a href="#">link</a></h5>
        </div>
        <div class="panel-foo">
            <p>no style&nbsp;<a href="#">link</a></p>
            <p class="lead">lead&nbsp;<a href="#">link</a></p>
            <p class="small">small&nbsp;<a href="#">link</a></p>
            <p class="initialism">initialism&nbsp;<a href="#">link</a></p>
            <blockquote class="blockquote">blockquote&nbsp;<a href="#">link</a></blockquote>
            <p class="text-muted">text-muted&nbsp;<a href="#">link</a></p>
            <p class="text-black">text-black&nbsp;<a href="#">link</a></p>
        </div>
        <div class="panel-foo">
            <p class="display-1">display-1</p>
            <p class="display-2">display-2</p>
            <p class="display-3">display-3</p>
            <p class="display-4">display-4</p>
        </div>
    </div>

    <!-- panels -->
    <div id="panels" class="container hide">
        <div class="panel">
            <pre><code>{{ '<div class="panel">' }}</code></pre>
            <h2>plain panel h2</h2>
            <p>plain panel paragraph</p>
            <a href="#">plain panel link</a>
        </div>
        <br>
        <div class="panel panel__blue">
            <pre><code>{{ '<div class="panel panel__blue">' }}</code></pre>
            <h2>blue panel h2</h2>
            <p>blue panel paragraph</p>
            <a href="#">blue panel link</a>
        </div>
        <br>
        <div class="panel panel__orange">
            <pre><code>{{ '<div class="panel panel__orange">' }}</code></pre>
            <h2>orange panel h2</h2>
            <p>orange panel paragraph</p>
            <a href="#">orange panel link</a>
        </div>
    </div>
    <!-- alerts -->
    <div id="alerts" class="container hide">
        <div class="panel-foo">
            See <a href="https://getbootstrap.com/docs/4.1/components/alerts" target="_blank">Bootstrap Alerts</a>
        </div>
        <div class="panel-foo">
            <p class="alert information-alert">information-alert text</p>
            <pre><code>{{ '<p class="alert information-alert">information-alert text</p>' }}</code></pre>
            <p class="alert alert-primary">alert-primary text</p>
            <pre><code>{{ '<p class="alert alert-primary">alert-primary text</p>' }}</code></pre>
            <p class="alert alert-secondary">alert-secondary text</p>
            <pre><code>{{ '<p class="alert alert-secondary">alert-secondary text</p>' }}</code></pre>
            <p class="alert alert-success">alert-success text</p>
            <pre><code>{{ '<p class="alert alert-success">alert-success text</p>' }}</code></pre>
            <p class="alert alert-info">alert-info text</p>
            <pre><code>{{ '<p class="alert alert-info">alert-info text</p>' }}</code></pre>
            <p class="alert alert-warning">alert-warning text</p>
            <pre><code>{{ '<p class="alert alert-warning">alert-warning text</p>' }}</code></pre>
            <p class="alert alert-danger">alert-danger text</p>
            <pre><code>{{ '<p class="alert alert-danger">alert-danger text</p>' }}</code></pre>
            <p class="alert alert-delete">alert-delete text</p>
            <pre><code>{{ '<p class="alert alert-delete">alert-delete text</p>' }}</code></pre>
            <p class="alert alert-light">alert-light text</p>
            <pre><code>{{ '<p class="alert alert-light">alert-light text</p>' }}</code></pre>
            <p class="alert alert-dark">alert-dark text</p>
            <pre><code>{{ '<p class="alert alert-dark">alert-dark text</p>' }}</code></pre>
        </div>
    </div>

    <!-- badges -->
    <div id="badges" class="container hide">
        <div class="panel-foo">
            See <a href="https://getbootstrap.com/docs/4.1/components/badges" target="_blank">Bootstrap Badges</a>
        </div>
        <div class="panel-foo">
            <span class="badge badge-primary">badge-primary</span>
            <pre><code>{{ '<span class="badge badge-primary">badge-primary</span>' }}</code></pre>
            <span class="badge badge-secondary">badge-secondary</span>
            <pre><code>{{ '<span class="badge badge-secondary">badge-secondary</span>' }}</code></pre>
            <span class="badge badge-success">badge-success</span>
            <pre><code>{{ '<span class="badge badge-success">badge-success</span>' }}</code></pre>
            <span class="badge badge-info">badge-info</span>
            <pre><code>{{ '<span class="badge badge-info">badge-info</span>' }}</code></pre>
            <span class="badge badge-warning">badge-warning</span>
            <pre><code>{{ '<span class="badge badge-warning">badge-warning</span>' }}</code></pre>
            <span class="badge badge-danger">badge-danger</span>
            <pre><code>{{ '<span class="badge badge-danger">badge-danger</span>' }}</code></pre>
            <span class="badge badge-light">badge-light</span>
            <pre><code>{{ '<span class="badge badge-light">badge-light</span>' }}</code></pre>
            <span class="badge badge-dark">badge-dark</span>
            <pre><code>{{ '<span class="badge badge-dark">badge-dark</span>' }}</code></pre>
        </div>
    </div>
    <!-- buttons -->
    <div id="buttons" class="container hide">
        <div class="panel-foo">
            See <a href="https://getbootstrap.com/docs/4.1/components/buttons" target="_blank">Bootstrap Buttons</a>
        </div>
        <div class="panel-foo">
            <button class="btn btn-primary">primary button</button>
            <pre><code>{{ '<button class="btn btn-primary">primary button</button>' }}</code></pre>
            <button class="btn btn-primary btn-sm">small primary button</button>
            <pre><code>{{ '<button class="btn btn-primary btn-sm">small primary button</button>' }}</code></pre>
            <button class="btn btn-outline-primary">outlined primary button</button>
            <pre><code>{{ '<button class="btn btn-outline-primary">outlined primary button</button>' }}</code></pre>
            <button class="btn btn-primary btn-rounded">primary rounded button</button>
            <pre><code>{{ '<button class="btn btn-primary btn-rounded">primary rounded button</button>' }}</code></pre>
        </div>
        <div class="panel-foo">
            <button class="btn btn-secondary">secondary button</button>
            <pre><code>{{ '<button class="btn btn-secondary">secondary button</button>' }}</code></pre>
            <button class="btn btn-secondary btn-sm">small secondary button</button>
            <pre><code>{{ '<button class="btn btn-secondary btn-sm">small secondary button</button>' }}</code></pre>
            <button class="btn btn-outline-secondary">outlined secondary button</button>
            <pre><code>{{ '<button class="btn btn-outline-secondary">outlined secondary button</button>' }}</code></pre>
            <button class="btn btn-secondary btn-rounded">secondary rounded button</button>
            <pre><code>{{ '<button class="btn btn-secondary btn-rounded">secondary rounded button</button>' }}</code></pre>
        </div>
        <div class="panel-foo">
            <button class="btn btn-info">button info</button>
            <pre><code>{{ '<button class="btn btn-info">button info</button>' }}</code></pre>
            <button class="btn btn-info btn-sm">small info button</button>
            <pre><code>{{ '<button class="btn btn-info btn-sm">small info button</button>' }}</code></pre>
            <button class="btn btn-outline-info">outlined info button</button>
            <pre><code>{{ '<button class="btn btn-outline-info">outlined info button</button>' }}</code></pre>
            <button class="btn btn-info btn-rounded">button info rounded</button>
            <pre><code>{{ '<button class="btn btn-info btn-rounded">button info rounded</button>' }}</code></pre>
        </div>
        <div class="panel-foo">
            <button class="btn btn-success">button success</button>
            <pre><code>{{ '<button class="btn btn-success">button success</button>' }}</code></pre>
            <button class="btn btn-success btn-sm">small success button</button>
            <pre><code>{{ '<button class="btn btn-success btn-sm">small success button</button>' }}</code></pre>
            <button class="btn btn-outline-success">outlined success button</button>
            <pre><code>{{ '<button class="btn btn-outline-success">outlined success button</button>' }}</code></pre>
            <button class="btn btn-success btn-rounded">button success rounded</button>
            <pre><code>{{ '<button class="btn btn-success btn-rounded">button success rounded</button>' }}</code></pre>
        </div>
        <div class="panel-foo">
            <button class="btn btn-warning">button warning</button>
            <pre><code>{{ '<button class="btn btn-warning">button warning</button>' }}</code></pre>
            <button class="btn btn-warning btn-sm">small warning button</button>
            <pre><code>{{ '<button class="btn btn-warning btn-sm">small warning button</button>' }}</code></pre>
            <button class="btn btn-outline-warning">outlined warning button</button>
            <pre><code>{{ '<button class="btn btn-outline-warning">outlined warning button</button>' }}</code></pre>
            <button class="btn btn-warning btn-rounded">button warning rounded</button>
            <pre><code>{{ '<button class="btn btn-warning btn-rounded">button warning rounded</button>' }}</code></pre>
        </div>
        <div class="panel-foo">
            <button class="btn btn-danger">button danger</button>
            <pre><code>{{ '<button class="btn btn-danger">button danger</button>' }}</code></pre>
            <button class="btn btn-danger btn-sm">small danger button</button>
            <pre><code>{{ '<button class="btn btn-danger btn-sm">small danger button</button>' }}</code></pre>
            <button class="btn btn-outline-danger">outlined danger button</button>
            <pre><code>{{ '<button class="btn btn-outline-danger">outlined danger button</button>' }}</code></pre>
            <button class="btn btn-danger btn-rounded">button danger rounded</button>
            <pre><code>{{ '<button class="btn btn-danger btn-rounded">button danger rounded</button>' }}</code></pre>
        </div>
        <div class="panel-foo">
            <button class="btn btn-light">light button</button>
            <pre><code>{{ '<button class="btn btn-light">light button</button>' }}</code></pre>
            <button class="btn btn-light btn-sm">small light button</button>
            <pre><code>{{ '<button class="btn btn-light btn-sm">small light button</button>' }}</code></pre>
            <button class="btn btn-outline-light">outlined light button</button>
            <pre><code>{{ '<button class="btn btn-outline-light">outlined light button</button>' }}</code></pre>
            <button class="btn btn-light btn-rounded">light rounded button</button>
            <pre><code>{{ '<button class="btn btn-light btn-rounded">light rounded button</button>' }}</code></pre>
        </div>
        <div class="panel-foo">
            <button class="btn btn-dark">dark button</button>
            <pre><code>{{ '<button class="btn btn-dark">dark button</button>' }}</code></pre>
            <button class="btn btn-dark btn-sm">small dark button</button>
            <pre><code>{{ '<button class="btn btn-dark btn-sm">small dark button</button>' }}</code></pre>
            <button class="btn btn-outline-dark">outlined dark button</button>
            <pre><code>{{ '<button class="btn btn-outline-dark">outlined dark button</button>' }}</code></pre>
            <button class="btn btn-dark btn-rounded">dark rounded button</button>
            <pre><code>{{ '<button class="btn btn-dark btn-rounded">dark rounded button</button>' }}</code></pre>
        </div>
        <div class="panel-foo">
            <button class="btn-link">button link</button>
            <pre><code>{{ '<button class="btn-link">button link</button>' }}</code></pre>
            <button class="btn-view">button view</button>
            <pre><code>{{ '<button class="btn-view">button view</button>' }}</code></pre>
            <button class="btn-preferences">button preferences</button>
            <pre><code>{{ '<button class="btn-preferences">button preferences</button>' }}</code></pre>
            <button class="btn-title">button title</button>
            <pre><code>{{ '<button class="btn-title">button title</button>' }}</code></pre>
        </div>
        <div class="panel-foo">
            <button class="btn alert alert-primary">alert-primary button</button>
            <pre><code>{{ '<button class="btn alert alert-primary">alert-primary button</button>' }}</code></pre>
            <button class="btn alert alert-secondary">alert-secondary button</button>
            <pre><code>{{ '<button class="btn alert alert-secondary">alert-secondary button</button>' }}</code></pre>
            <button class="btn alert alert-success">alert-success button</button>
            <pre><code>{{ '<button class="btn alert alert-success">alert-success button</button>' }}</code></pre>
            <button class="btn alert alert-info">alert-info button</button>
            <pre><code>{{ '<button class="btn alert alert-info">alert-info button</button>' }}</code></pre>
            <button class="btn alert alert-warning">alert-warning button</button>
            <pre><code>{{ '<button class="btn alert alert-warning">alert-warning button</button>' }}</code></pre>
            <button class="btn alert alert-danger">alert-danger button</button>
            <pre><code>{{ '<button class="btn alert alert-danger">alert-danger button</button>' }}</code></pre>
            <button class="btn alert alert-delete">alert-delete button</button>
            <pre><code>{{ '<button class="btn alert alert-delete">alert-delete button</button>' }}</code></pre>
            <button class="btn alert alert-light">alert-light button</button>
            <pre><code>{{ '<button class="btn alert alert-light">alert-light button</button>' }}</code></pre>
            <button class="btn alert alert-dark">alert-dark button</button>
            <pre><code>{{ '<button class="btn alert alert-dark">alert-dark button</button>' }}</code></pre>
        </div>
        <div class="panel-foo">
            <a href="#" class="btn alert alert-primary">alert-primary link button</a>
            <pre><code>{{ '<a href="#" class="btn alert alert-primary">alert-primary link button</a>' }}</code></pre>
            <a href="#" class="btn alert alert-secondary">alert-secondary link button</a>
            <pre><code>{{ '<a href="#" class="btn alert alert-secondary">alert-secondary link button</a>' }}</code></pre>
            <a href="#" class="btn alert alert-success">alert-success link button</a>
            <pre><code>{{ '<a href="#" class="btn alert alert-success">alert-success link button</a>' }}</code></pre>
            <a href="#" class="btn alert alert-info">alert-info link button</a>
            <pre><code>{{ '<a href="#" class="btn alert alert-info">alert-info link button</a>' }}</code></pre>
            <a href="#" class="btn alert alert-warning">alert-warning link button</a>
            <pre><code>{{ '<a href="#" class="btn alert alert-warning">alert-warning link button</a>' }}</code></pre>
            <a href="#" class="btn alert alert-danger">alert-danger link button</a>
            <pre><code>{{ '<a href="#" class="btn alert alert-danger">alert-danger link button</a>' }}</code></pre>
            <a href="#" class="btn alert alert-delete">alert-delete link button</a>
            <pre><code>{{ '<a href="#" class="btn alert alert-delete">alert-delete link button</a>' }}</code></pre>
            <a href="#" class="btn alert alert-light">alert-light link button</a>
            <pre><code>{{ '<a href="#" class="btn alert alert-light">alert-light link button</a>' }}</code></pre>
            <a href="#" class="btn alert alert-dark">alert-dark link button</a>
            <pre><code>{{ '<a href="#" class="btn alert alert-dark">alert-dark link button</a>' }}</code></pre>
        </div>

        <!-- <p>Misc</p>
            <button class="btn btn-fault-info">button.btn.btn-fault-info</button>
            <button class="btn btn-fault-option">button.btn.btn-fault-option</button>
            <button class="btn-column">button.btn-column</button>
            <button class="btn btn-tertiary">button.btn.btn-tertiary</button>
            <button class="btn btn-tertiary btn-rounded">button.btn.btn-tertiary.btn-rounded</button>
            <button class="btn dropdown-toggle">button.btn.dropdown-toggle</button> -->
    </div>
    </div>
    <!-- inputs -->
    <div id="inputs" class="container hide">
        <div class="panel-foo">
            See <a href="https://getbootstrap.com/docs/4.1/components/forms" target="_blank">Bootstrap Forms</a>
        </div>
        <div class="panel-foo">
            <div class="form-group">
                <label for="exampleInputEmail1">Email address</label>
                <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter email">
                <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small>
            </div>
            <div class="form-group form-check">
                <input type="checkbox" class="btn-checkbox" id="exampleCheck1">
                <label class="form-check-label" for="exampleCheck1">&nbsp;Opt in</label>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
            <pre><code>{{ 'div class="form-group">
    <label for="exampleInputEmail1">Email address</label>
    <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter email">
    <small id="emailHelp" class="form-text text-muted">We no share this.</small>
</div>
<div class="form-group form-check">
    <input type="checkbox" class="btn-checkbox" id="exampleCheck1">
    <label class="form-check-label" for="exampleCheck1">&nbsp;Opt in</label>
</div>
<button type="submit" class="btn btn-primary">Submit</button>' }}</code></pre>
        </div>
    </div>
    <!-- cards -->
    <div id="cards" class="container hide">
        <div class="panel-foo">
            <div class="card__content">
                <p> h2 ~ </p>
                <p> paragraph ~ </p>
                <a href="#"> link</a>
            </div>
            <pre><code>{{ '<div class="card__content">
    <p> h2 ~ </p>
    <p> paragraph ~ </p>
    <a href="#"> link</a>
</div>' }}</code></pre>

            <ul class="card__content">
                <li class="card__content">list item</li>
                <li class="card__content">list item</li>
                <li class="card__content">list item</li>
                <li class="card__content">list item</li>
            </ul>
            <pre><code>{{ '<ul class="card__content">
    <li class="card__content">list item</li>
    <li class="card__content">list item</li>
    <li class="card__content">list item</li>
    <li class="card__content">list item</li>
</ul>' }}</code></pre>
        </div>
        <div class="panel-foo">
            <div class="block">
                <p>block h2</p>
                <p>block paragraph</p>
                <a href="#">block link</a>
            </div>
            <pre><code>{{ '<div class="block">
    <p>block h2</p>
    <p>block paragraph</p>
    <a href="#">block link</a>
</div>' }}</code></pre>
            <ul class="block">
                <li>list item</li>
                <li>list item</li>
                <li>list item</li>
                <li>list item</li>
            </ul>
            <pre><code>{{ '<ul class="block">
    <li>list item</li>
    <li>list item</li>
    <li>list item</li>
    <li>list item</li>
</ul>' }}</code></pre>
        </div>
    </div>
    <!-- tables -->
    <div id="tables" class="container hide">
        <div class="panel-foo">
            See <a href="https://getbootstrap.com/docs/4.1/content/tables/" target="_blank">Bootstrap Tables</a>
        </div>
        <div class="panel-foo">
            <table class="table">
                <caption>caption</caption>
                <th class="table-heading">header</th>
                <th>header</th>
                <tr>
                    <td>data</td>
                    <td>data</td>
                </tr>
                <tr>
                    <td>data</td>
                    <td>data</td>
                </tr>
                <tr>
                    <td>data</td>
                    <td>data</td>
                </tr>
            </table>
        </div>
        <pre><code>{{ '<table class="table">
    <caption>caption</caption>
    <th>header</th>
    <th>header</th>
    <tr>
        <td>data</td>
        <td>data</td>
    </tr>
    <tr>
        <td>data</td>
        <td>data</td>
    </tr>
    <tr>
        <td>data</td>
        <td>data</td>
    </tr>
</table>' }}</code></pre>
    </div>
    <!-- images -->
    <div id="images" class="container hide">
        <div class="panel-foo">
            See <a href="https://getbootstrap.com/docs/4.1/content/images/" target="_blank">Bootstrap Images</a>
        </div>
        <div class="panel-foo">
            <img class="img-thumbnail" src="{{ asset('images/community.jpg') }}" width="200" alt="community" />
            <pre><code>{{ '<img class="img-thumbnail" src="..." width="200" alt="community" />' }}</code></pre>
        </div>
        <div class="panel-foo">
            <img class="img-fluid" src="{{ asset('images/community.jpg') }}" alt="community" />
            <pre><code>{{ '<img class="img-fluid" src="..." alt="community" />' }}</code></pre>
        </div>
        <div class="panel-foo">
            <figure class="figure">
                <img class="figure-img" src="{{ asset('images/community.jpg') }}" width="300" alt="community" />
                <figcaption class="figure-caption">Figure caption</figcaption>
            </figure>
            <pre><code>{{ '<figure class="figure">
    <img class="figure-img" src="..." width="300" alt="community" />
    <figcaption class="figure-caption">Figure caption</figcaption>
</figure>' }}</code></pre>
        </div>
        <div class="panel-foo">
            <ul class="photo-list">
                <li><img src="{{ asset('images/community.jpg') }}" width="200" alt="community" /></li>
                <li><img src="{{ asset('images/community.jpg') }}" width="200" alt="community" /></li>
                <li><img src="{{ asset('images/community.jpg') }}" width="200" alt="community" /></li>
                <li><img src="{{ asset('images/community.jpg') }}" width="200" alt="community" /></li>
                <li><img src="{{ asset('images/community.jpg') }}" width="200" alt="community" /></li>
                <li><img src="{{ asset('images/community.jpg') }}" width="200" alt="community" /></li>
                <li><img src="{{ asset('images/community.jpg') }}" width="200" alt="community" /></li>
                <li><img src="{{ asset('images/community.jpg') }}" width="200" alt="community" /></li>
            </ul>
            <pre><code>{{ '<ul class="photo-list">
    <li><img src="..." width="200" alt="community" /></li>
    <li><img src="..." width="200" alt="community" /></li>
    <li><img src="..." width="200" alt="community" /></li>
    <li><img src="..." width="200" alt="community" /></li>
    <li><img src="..." width="200" alt="community" /></li>
    <li><img src="..." width="200" alt="community" /></li>
    <li><img src="..." width="200" alt="community" /></li>
    <li><img src="..." width="200" alt="community" /></li>
</ul>' }}</code></pre>
        </div>
    </div>

</section>

@endsection

@section('scripts')
<script>
    document.addEventListener(`DOMContentLoaded`, async () => {

        /**
         * List all the classes used on buttons for testing purposes.
         * See the test controller.
        */
        function listClasses() {

            var classes = ['buttons'];
            const buttons = [];
            [...document.querySelectorAll('.panel-foo button')].forEach(e => {
                for(var i=0;i<e.classList.length;i++) {
                    buttons.push(e.classList[i]);
                }
            });
            classes['buttons'] = buttons.filter(function(item, pos, self) {
                return self.indexOf(item) == pos;
            })
            console.log(classes);
        }

        [...document.querySelectorAll('.nav-link-foo')].forEach(e1 => {

            e1.addEventListener('click', function(e2) {
                e2.preventDefault();
                hideAll();
                e2.target.classList.add('active');
                document.getElementById(e2.target.dataset.toggle).classList.remove('hide');
            });

        });

        function hideAll() {

            [...document.querySelectorAll('.nav-link-foo')].forEach(e => {
                e.classList.remove('active');
                document.getElementById(e.dataset.toggle).classList.add('hide');
            });
        }

    }, false);
</script>

@endsection