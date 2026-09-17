{{-- À inclure une fois dans @section('script') des pages qui utilisent choix-patient. --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.assurance-choix-patient').forEach(function (bloc) {
            var champ = bloc.querySelector('.js-recherche');
            var resultats = bloc.querySelector('.js-resultats');
            var minuterie = null;

            champ.addEventListener('input', function () {
                clearTimeout(minuterie);
                bloc.querySelector('.js-patient-id').value = '';
                bloc.querySelector('.js-choisi').textContent = '';
                var terme = champ.value.trim();
                if (terme.length < 2) { resultats.innerHTML = ''; return; }

                minuterie = setTimeout(function () {
                    fetch(bloc.dataset.url + '?q=' + encodeURIComponent(terme), { headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.ok ? r.json() : []; })
                        .then(function (donnees) {
                            var liste = Array.isArray(donnees) ? donnees : (donnees.patients || donnees.data || []);
                            resultats.innerHTML = '';
                            if (!liste.length) {
                                resultats.innerHTML = '<div class="list-group-item small text-muted">Aucun patient trouvé. Pour un patient d\'une autre clinique, saisissez son numéro complet ou son identifiant national.</div>';
                                return;
                            }
                            liste.forEach(function (p) {
                                var el = document.createElement('button');
                                el.type = 'button';
                                el.className = 'list-group-item list-group-item-action small';
                                el.textContent = p.nom + (p.identifiant ? ' — ' + p.identifiant : '') + (p.telephone ? ' — ' + p.telephone : '');
                                el.addEventListener('click', function () {
                                    bloc.querySelector('.js-patient-id').value = p.id;
                                    bloc.querySelector('.js-preuve').value = terme;
                                    bloc.querySelector('.js-choisi').textContent = '✓ ' + p.nom;
                                    champ.value = p.nom;
                                    resultats.innerHTML = '';
                                });
                                resultats.appendChild(el);
                            });
                        });
                }, 300);
            });
        });
    });
    </script>
