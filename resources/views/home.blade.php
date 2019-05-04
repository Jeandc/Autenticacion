@extends('layouts.app')
@section('content')
<div class="container">
        <div class="alert alert-warning" role="alert" id="exit">
            <div style="background-color: transparent;">
                <form method="POST" action="{{route ('logout')}}">
                    {{ csrf_field() }}
                    <div style="text-align: right;">DESEA SALIR DE SESION <button class="btn btn-success btn-xs">SI</button></div>  
                </form>
            </div>
        </div> 
        <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    Inicio de sesion correcto
        </div>
</div>
@endsection
