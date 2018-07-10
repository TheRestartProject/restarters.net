@include('fixometer/layouts/header_plain')
@yield('content')

<section style="padding-top:50px;padding-bottom:50px;">
  <div class="container">

    <div class="row">
      <div class="col-md-8">

        <h2>Alert with button</h2>

        <div class="alert alert-primary" role="alert">
          <div class="row">
            <div class="col-md-8 col-lg-9 d-flex flex-column align-content-center">Cras mattis consectetur purus sit amet fermentum</div>
            <div class="col-md-4 col-lg-3 d-flex flex-column align-content-center">
              <button class="btn">Tortor Pharet</button>
            </div>
          </div>
        </div>

        <div class="alert alert-success" role="alert">
          <div class="row">
            <div class="col-md-8 col-lg-9 d-flex flex-column align-content-center">Cras mattis consectetur purus sit amet fermentum</div>
            <div class="col-md-4 col-lg-3 d-flex flex-column align-content-center">
              <button class="btn">Tortor Pharet</button>
            </div>
          </div>
        </div>

        <div class="alert alert-danger" role="alert">
          <div class="row">
            <div class="col-md-8 col-lg-9 d-flex flex-column align-content-center">Cras mattis consectetur purus sit amet fermentum</div>
            <div class="col-md-4 col-lg-3 d-flex flex-column align-content-center">
              <button class="btn">Tortor Pharet</button>
            </div>
          </div>
        </div>

        <div class="alert alert-delete" role="alert">
          <div class="row">
            <div class="col-md-8 col-lg-9 d-flex flex-column align-content-center">Cras mattis consectetur purus sit amet fermentum</div>
            <div class="col-md-4 col-lg-3 d-flex flex-column align-content-center">
              <button class="btn">Tortor Pharet</button>
            </div>
          </div>
        </div>

        <h2>Alert without button</h2>

        <div class="alert alert-primary" role="alert">
          <div class="row">
            <div class="col-md-12 d-flex flex-column align-content-center">Cras mattis consectetur purus sit amet fermentum</div>
          </div>
        </div>

        <h2>Buttons</h2>

        <button class="btn btn-primary">Button</button>
        <button class="btn btn-secondary">Button</button>
        <button class="btn btn-danger">Button</button>
        <button class="btn btn-primary" disabled>Button</button>

      </div>
    </div>

  </div>
</section>

