<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0-2/css/all.min.css">
        <link href="{{ url('bootstrap.min.css')}}" rel="stylesheet" type="text/css" />
        <!--<link href="{{ url('custom.css')}}" rel="stylesheet" type="text/css" />-->
        <style>
            .pagination {
                justify-content: center;
                margin-top: 20px;
            }
        
            .pagination .page-item {
                margin: 0 5px;
            }
            nav#prayed-side-nav {
                position: fixed;
                width: 60px;
                height: 100%;
                background: #52575d;
                transition: .5s;
                overflow: hidden;
            }
            
            nav#prayed-side-nav.active {
                width: 300px;
            }
            nav#prayed-side-nav ul {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
            }
            
            nav#prayed-side-nav ul li {
                list-style: none;
                width: 100%;
                position: relative;
            
            }
            
            nav#prayed-side-nav ul li a:hover {
                color: #41444b;
            }
            
            nav#prayed-side-nav ul li:hover a::before {
                background-color: #f6f4e6;
                width: 100%;
            }
            
            nav#prayed-side-nav ul li a {
                position: relative;
                display: block;
                width: 100%;
                display: flex;
                align-items: center;
                text-decoration: none;
                color: #f6f4e6;
                transition: .2s;
            }
            
            nav#prayed-side-nav ul li a::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                width: 0;
                height: 100%;
                transition: .2s;
            }
            
            nav#prayed-side-nav ul li a .icon {
                position: relative;
                display: block;
                min-width: 60px;
                height: 60px;
                line-height: 60px;
                text-align: center;
            }
            
            nav#prayed-side-nav ul li a .title {
                position: relative;
                font-size: .85em;
            }
            
            nav#prayed-side-nav ul li a .icon * {
                font-size: 1.1em;
            }
            
            nav#prayed-side-nav ul li a.toggle {
                border-bottom: 3px solid #41444b;
            }
            
            .toggle {
                cursor: pointer;
            }
            
            
            @media (max-width: 768px) {
                nav#prayed-side-nav {
                    left: -60px;
                }
            
                nav#prayed-side-nav.active {
                    left: 0;
                    width: 100%;
                }
            
                nav#prayed-side-nav ul li a.toggle {
                    display: none;
                }
            }
            nav#prayed-side-nav.active {
                width: 180px;
            }
            .prayedfor-cont.active {
              margin-left: 200px;
            }
            .prayedfor-cont {
              margin-left: 70px;
            }
            :root {
                --bg-primary: #41444b;
                --bg-secondary: #52575d;
                --bg-active: #f6f4e6;
                --cl-text: #f6f4e6;
            }
            .position-rel{
                position: relative;
            }
            .prayedfor_loader {
                position: absolute;
                top: 50%;
                left: 50%;
                border: 8px solid #f3f3f3;
                border-top: 8px solid #3498db;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                animation: spin 2s linear infinite;
                margin: 0 auto;
                margin-top: 20px;
                display: none; /* Initially hidden */
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    </head>
    <body>
        <div id="prayeed-app">
            <main>
                <nav id="prayed-side-nav">
                    <ul id="menuLinks" style="display:none;" class="ps-0">
                        <li>
                            <a class="toggle">
                                <span class="icon"><i class="fas fa-bars"></i></span>
                                <span class="title">PrayedFor.io</span>
                            </a>
                        </li>
                        <li>
                            <a href="/">
                                <span class="icon"><i class="fas fa-home"></i></span>
                                <span class="title">Home</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('church.listContacts') }}">
                                <span class="icon"><i class="fas fa-users"></i></span>
                                <span class="title">Contacts</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('church.viewGlobalCustomMesage') }}">
                                <span class="icon"><i class="fa fa-reply-all"></i></span>
                                <span class="title">Global Message</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('church.settings') }}">
                                <span class="icon"><i class="fas fa-cog"></i></span>
                                <span class="title">Setting</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <div class="prayedfor-cont container py-4">
                    @yield('content')
                </div>
            </main>
        </div>
        
        <script>
            var isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
            var isInsideIframe = window.self !== window.top;
        
            if (isSafari || isInsideIframe) {
                // If Safari and not inside an iframe, display the Safari-specific content
                document.getElementById('menuLinks').style.display = 'block';
            } else {
                // If not Safari or inside an iframe, hide the menu links
                document.getElementById('menuLinks').style.display = 'none';
            }
        </script>
        <script src="{{ url('popper.min.js')}}"></script>
        <script src="{{ url('bootstrap.bundle.min.js')}}"></script>
        <script src="{{ url('bootstrap.min.js')}}"></script>
        <script>
            var getSidebar = document.querySelector('nav');
            var getToggle = document.getElementsByClassName('toggle');
            var getMain = document.querySelector('.prayedfor-cont');
            
            for (var i = 0; i < getToggle.length; i++) {
              getToggle[i].addEventListener('click', function () {
                getSidebar.classList.toggle('active');
                getMain.classList.toggle('active');
              });
            }
        </script>
        
        <script>
            // Hide alert response
            let alertDiv = document.querySelector('.pray-alert-message');
            function hideAlert() {
                if (alertDiv) {
                    alertDiv.style.display = 'none';
                }
            }
            setTimeout(hideAlert, 5000);
            
            //Loader in global msg form submission
            document.addEventListener('DOMContentLoaded', function () {
                var loader = document.getElementById('prayedfor_loader');
                if (loader) {
                    var form = document.getElementById('global_msg_prayedfor');
                    loader.style.display = 'none';
                    // On form submission, show the loader
                    form.addEventListener('submit', function () {
                        loader.style.display = 'block';
                    });
                }
            });
        </script>
    </body>
</html>
