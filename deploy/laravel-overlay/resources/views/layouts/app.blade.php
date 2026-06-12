<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TernakKu') — Marketplace Ternak Berbasis Data</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50:'#f0fdf4',100:'#dcfce7',200:'#bbf7d0',300:'#86efac',
                            400:'#4ade80',500:'#22c55e',600:'#16a34a',700:'#15803d',
                            800:'#166534',900:'#14532d',950:'#052e16'
                        }
                    },
                    fontFamily: { sans: ['Inter','system-ui','sans-serif'] },
                    keyframes: {
                        'fade-up': { '0%':{opacity:0,transform:'translateY(20px)'}, '100%':{opacity:1,transform:'translateY(0)'} },
                        'float':   { '0%,100%':{transform:'translateY(0)'}, '50%':{transform:'translateY(-12px)'} },
                        'gradient':{ '0%,100%':{backgroundPosition:'0% 50%'}, '50%':{backgroundPosition:'100% 50%'} },
                        'pop':     { '0%':{transform:'scale(.9)',opacity:0}, '100%':{transform:'scale(1)',opacity:1} },
                    },
                    animation: {
                        'fade-up':'fade-up .7s ease-out both',
                        'float':'float 6s ease-in-out infinite',
                        'gradient':'gradient 8s ease infinite',
                        'pop':'pop .4s ease-out both',
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak]{display:none!important}
        .bg-mesh{background:linear-gradient(120deg,#052e16,#14532d,#15803d,#16a34a);background-size:300% 300%}
        .delay-1{animation-delay:.1s}.delay-2{animation-delay:.25s}.delay-3{animation-delay:.4s}.delay-4{animation-delay:.55s}
    </style>
</head>
<body class="font-sans bg-brand-50 text-brand-950 antialiased">
    @yield('content')
</body>
</html>
