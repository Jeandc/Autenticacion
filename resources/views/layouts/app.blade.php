<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="{{mix('css/app.css')}}">
    <title>SocialApp</title>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light navbar-socialApp">
    <div class="container">
        <a class="navbar-brand" href="{{route('home')}}">SocialApp</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">

            <ul class="navbar-nav mx-auto">
                {{--<li class="nav-item active">--}}
                    {{--<a class="nav-link" href="#">Inicio <span class="sr-only">(current)</span></a>--}}
                {{--</li>--}}
                {{--<li class="nav-item">--}}
                    {{--<a class="nav-link" href="#">Link</a>--}}
                {{--</li>--}}
            </ul>
                <ul class="navbar-nav ml-auto">
                    @guest
                        <li class="nav-item"><a href="{{route('login')}}" class="nav-link">login</a></li>
                        <li class="nav-item"><a href="{{route('register')}}" class="nav-link">Registrate</a></li>
                    @else
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ auth::user()->NOMBRE_USUARIO }}
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="#">Another action</a>
                                <div class="dropdown-divider"></div>
                                <a onclick="document.getElementById('logout').submit()" class="dropdown-item" href="#">Cerrar Sesion</a>
                            </div>
                        </li>
                     @endguest
                </ul>
            <form id="logout" method="POST" action="{{route ('logout')}}">{{ csrf_field() }}</form>

        </div>
    </div>
</nav>
        <main class="py-4">
            @yield('content')
        </main>

    <script src="{{mix('js/app.js')}}"></script>
</body>
</html>