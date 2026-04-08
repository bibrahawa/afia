<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta charset="UTF-8">
    <title>Aprosafe - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />

    {{-- <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="Aprosafe" />
    <link rel="manifest" href="/site.webmanifest" /> --}}

    <link
      rel="icon"
      href="{{asset('assets/img/kaiadmin/favicon.ico')}}"
      type="image/x-icon"
    />

    <script src="{{ asset("assets/js/plugin/webfont/webfont.min.js") }} "></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: {
          families: [
            "Font Awesome 5 Solid",
            "Font Awesome 5 Regular",
            "Font Awesome 5 Brands",
            "simple-line-icons",
          ],
          urls: ["{{ asset("assets/css/fonts.min.css") }}"],
        },
        active: function () {
          sessionStorage.fonts = true;
        },
      });
    </script>

    <link rel="stylesheet" href="{{asset("assets/css/bootstrap.min.css")}}" />
    <link rel="stylesheet" href="{{asset("assets/css/plugins.min.css")}}" />
    <link rel="stylesheet" href="{{asset("assets/css/kaiadmin.min.css")}}" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">

    {{-- Tailwind CSS pour la prévisualisation (le CDN doit être dans le head pour être analysé avant le rendu) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        medical: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1'
                        }
                    }
                }
            }
        }
    </script>

    @yield('style') {{-- Pour les styles spécifiques à une page --}}
    
    <!-- jQuery EN PREMIER -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script src="{{ asset("assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js") }} "></script>
    <script src="{{ asset("assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js") }} "></script>
  </head>
  <body>
    <div class="wrapper">

        @include("layouts.sidebar")

      <div class="main-panel">
        <div class="main-header">
          <div class="main-header-logo">
            <div class="logo-header" data-background-color="orange">
              {{-- <a href="index.html" class="logo">
                <img
                  src="{{ asset("assets/img/kaiadmin/logo_light.svg")}}"
                  alt="navbar brand"
                  class="navbar-brand"
                  height="20"
                />
              </a> --}}
              <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                  <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                  <i class="gg-menu-left"></i>
                </button>
              </div>
              <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
              </button>
            </div>
            </div>
          @include("layouts.navbar")
          </div>

        @yield("content") {{-- C'est ici que le contenu de vos pages sera injecté --}}

        @include("layouts.footer")
      </div>

    </div>
    
    <script src="{{ asset("assets/js/core/popper.min.js") }} "></script>
    <script src="{{ asset("assets/js/core/bootstrap.min.js") }} "></script>

    <script src="{{ asset("assets/js/plugin/chart.js/chart.min.js") }} "></script>
    <script src="{{ asset("assets/js/plugin/chart-circle/circles.min.js") }} "></script>
    <script src="{{ asset("assets/js/plugin/datatables/datatables.min.js") }} "></script>
    <script src="{{ asset("assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js") }} "></script>
    <script src="{{ asset("assets/js/plugin/jsvectormap/jsvectormap.min.js") }} "></script>
    <script src="{{ asset("assets/js/plugin/jsvectormap/world.js") }} "></script>
    <script src="{{ asset("assets/js/plugin/sweetalert/sweetalert.min.js") }} "></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>


    <script src="{{ asset("assets/js/kaiadmin.min.js") }} "></script>

    <script>

      $(document).ready(function () {
          const table = $('#add-row');
          if (table.length && table.find('tbody tr').length) {
              table.DataTable({ pageLength: 15 });
          }

          // ✅ Destruction puis réinitialisation propre
          function initSelectPickers(context) {
              $(context || 'body').find('.selectpicker').each(function() {
                  // Détruire si déjà initialisé
                  if ($(this).data('selectpicker')) {
                      $(this).selectpicker('destroy');
                  }
                  // Réinitialiser proprement
                  $(this).selectpicker({
                      liveSearch: true,
                      liveSearchPlaceholder: 'Rechercher...',
                      noneResultsText: 'Aucun résultat pour {0}',
                      noneSelectedText: 'Sélectionner...'
                  });
              });
          }

          initSelectPickers();

          $(document).on('shown.bs.modal', function(e) {
              initSelectPickers(e.target);
          });
      });

    </script>

    <script>
        $("#lineChart").sparkline([102, 109, 120, 99, 110, 105, 115], {
          type: "line", height: "70", width: "100%", lineWidth: "2", lineColor: "#177dff", fillColor: "rgba(23, 125, 255, 0.14)",
        });
        $("#lineChart2").sparkline([99, 125, 122, 105, 110, 124, 115], {
          type: "line", height: "70", width: "100%", lineWidth: "2", lineColor: "#f3545d", fillColor: "rgba(243, 84, 93, .14)",
        });
        $("#lineChart3").sparkline([105, 103, 123, 100, 95, 105, 115], {
          type: "line", height: "70", width: "100%", lineWidth: "2", lineColor: "#ffa534", fillColor: "rgba(255, 165, 52, .14)",
        });
    </script>

    <script>
        $(document).ready(function() {
            // Success notification
            @if(session('success'))
                $.notify({
                    icon: 'fas fa-check',
                    title: 'Succès!',
                    message: '{{ session("success") }}'
                }, {
                    type: 'success', placement: { from: "bottom", align: "right" }, timer: 3000,
                    animate: { enter: 'animated fadeInRight', exit: 'animated fadeOutRight' }
                });
            @endif

            // Error notification
            @if(session('error'))
                $.notify({
                    icon: 'fas fa-exclamation-triangle',
                    title: 'Erreur!',
                    message: '{{ session("error") }}'
                }, {
                    type: 'danger', placement: { from: "bottom", align: "right" }, timer: 3000,
                    animate: { enter: 'animated fadeInRight', exit: 'animated fadeOutRight' }
                });
            @endif
        });
    </script>
    {{-- <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script> --}}

    @yield('script')

  </body>
</html>
