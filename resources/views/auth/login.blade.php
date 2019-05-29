@extends('layouts.app')
@section('content')
@include('flash::message')
    <div class="container">
        <div class="row">
            <div class="col-md-6 mx-auto">
                <div class="card border-0 bg-light">
                <div class="card-header">{{ __('Ingreso al Sistema') }}</div>


                <div class="card-body">
                    <form method="POST" action="{{ route('login') }}">
                        {{csrf_field()}}

                        <div class="form-group row">
                            <label for="email" class="col-sm-4 col-form-label text-md-right">{{ __('Direccion de Correo') }}</label>

                            <div class="card-body">
                                <input id="email"
                                       type="text"
                                       class="form-control border-0 {{ $errors->has('email') ? ' is-invalid' : '' }}"
                                       name="email"
                                       value="{{ old('email') }}"
                                       placeholder="Ingresa tu correo"
                                       required autofocus>

                                @if ($errors->has('email'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password" class="col-md-4 col-form-label text-md-right">{{ __('Contraseña') }}</label>

                            <div class="card-body">
                                <input id="password"
                                       type="password"
                                       class="form-control border-0 {{ $errors->has('password') ? ' is-invalid' : '' }}"
                                       name="password"
                                       placeholder="Ingresa tu contraseña"
                                       required>

                                @if ($errors->has('password'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button id="login-btn" type="submit" class="btn btn-primary btn-block">
                                    {{ __('Entrar') }}
                                </button>

                                <a class="btn btn-link" href="{{ route('password.request') }}">
                                    {{ __('Olvido su Contraseña?') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
