<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-default">
                    <div class="card-header">Example Component</div>

                    <div class="card-body">
                        I'm an example component using old-school classes on divs.
                    </div>
                </div>
            </div>
          <b-card>
            <b-card-header>
              Example New Component
            </b-card-header>
            <b-card-body>
              I'm a new-style component using Bootstrap Vue.
            </b-card-body>
          </b-card>
        </div>
    </div>
</template>

<script>
    export default {
        mounted() {
            console.log('Component mounted.')
        }
    }
</script>
