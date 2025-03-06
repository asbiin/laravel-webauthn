<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="author" content="Alexis Saettler" />
  <!--
    This file is part of asbiin/laravel-webauthn project.

    @copyright Alexis SAETTLER © 2019–2022
    @license MIT
  -->

  <title>{{ config('app.name', 'Laravel') }}</title>

  <!-- Scripts -->
  <script src="https://unpkg.com/axios@1.2.6/dist/axios.min.js" integrity="sha384-TiLmfbX5iPJnd6NZIGGUBlE7UcHkicSKG+1z9u33gDb40R39mnvlpvUlEekwxSd6" crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
  <script src="{!! secure_asset('vendor/webauthn/webauthn.js') !!}"></script>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

  <!-- Styles -->
  <link href="{{ asset('css/app.css') }}" rel="stylesheet">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>
<body>
  <div id="app">
    <main class="py-4">
      <form id="form">
        <div class="container">
          <div class="row justify-content-center">
            <div class="col-xs-12 col-md-6">
              <div class="card">
                <div class="card-header">{{ trans('webauthn::messages.register.title') }}</div>

                <div class="card-body">
                  <div class="alert alert-danger d-none" role="alert" id="error"></div>
                  <div class="alert alert-success d-none" role="alert" id="success">
                    {{ trans('webauthn::messages.success') }}
                  </div>

                  <h3 class="card-title">
                    {{ trans('webauthn::messages.insertKey') }}
                  </h3>

                  <p class="card-text">
                    <input type="text" id="name" placeholder="{{ trans('webauthn::messages.key_name') }}" />
                  </p>

                  <p class="card-text text-center">
                    <img src="https://ssl.gstatic.com/accounts/strongauth/Challenge_2SV-Gnubby_graphic.png" alt=""/>
                  </p>

                  <p class="card-text">
                    {{ trans('webauthn::messages.buttonAdvise') }}
                    <br />
                    {{ trans('webauthn::messages.noButtonAdvise') }}
                  </p>

                  <button type="submit" class="card-link" aria-pressed="true">{{ trans('webauthn::messages.submit') }}</button>
                  <a href="/" class="card-link" aria-pressed="true">{{ trans('webauthn::messages.cancel') }}</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>

    </main>
  </div>

  <script>
    var publicKey = {!! json_encode($publicKey) !!};

    var errors = {
      key_already_used: "{{ trans('webauthn::errors.key_already_used') }}",
      key_not_allowed: "{{ trans('webauthn::errors.key_not_allowed') }}",
      not_secured: "{{ trans('webauthn::errors.not_secured') }}",
      not_supported: "{{ trans('webauthn::errors.not_supported') }}",
    };

    function errorMessage(name, message) {
      switch (name) {
      case 'InvalidStateError':
        return errors.key_already_used;
      case 'NotAllowedError':
        return errors.key_not_allowed;
      default:
        return message;
      }
    }

    function error(message) {
      $('#error').text(message).removeClass('d-none');
    }

    var webauthn = new WebAuthn((name, message) => {
       error(errorMessage(name, message));
    });

    if (! webauthn.webAuthnSupport()) {
      switch (webauthn.notSupportedMessage()) {
        case 'not_secured':
          error(errors.not_secured);
          break;
        case 'not_supported':
          error(errors.not_supported);
          break;
      }
    }

    function start() {
      webauthn.register(
        publicKey,
        function (data) {
          $('#success').removeClass('d-none');
          axios.post("{{ route('webauthn.store') }}", {
            ...data,
            name: $('#name').val(),
          })
            .then(function (response) {
              if (response.data.callback) {
                window.location.href = response.data.callback;
              }
            })
            .catch(function (error) {
              console.log(error);
            });
        }
      );
    }

    $('#form').submit(function (e) {
      e.preventDefault();
      start();
    });
  </script>
</body>
</html>
