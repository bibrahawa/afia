<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Auto-Resize</title>
    <style>
        .card-body-service-seach {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            /* La card va s'adapter automatiquement */
            min-height: auto;
            height: auto;
            transition: all 0.3s ease;
        }

        .form-group-enhanced {
            margin-bottom: 0;
        }

        .form-label-enhanced {
            display: flex;
            align-items: center;
            font-weight: 600;
            margin-bottom: 12px;
            color: #333;
        }

        .status-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 500;
        }

        .status-optional {
            background: #e3f2fd;
            color: #1976d2;
        }

        .search-select {
            position: relative;
            width: 100%;
        }

        .search-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .search-input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .selected-items {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
            min-height: 40px;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background: white;
            align-items: flex-start;
            align-content: flex-start;
            /* Animation pour le redimensionnement */
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .selected-items:empty::before {
            content: "Aucun élément sélectionné";
            color: #6c757d;
            font-style: italic;
            display: block;
            width: 100%;
            text-align: center;
            line-height: 16px;
        }

        .selected-item {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideIn 0.3s ease-out;
            transition: all 0.2s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .remove-item {
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .remove-item:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .dropdown-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e9ecef;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            /* Animation d'ouverture */
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.2s ease;
        }

        .dropdown-list.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f1f3f4;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
        }

        .dropdown-item.selected {
            background: #e7f3ff;
            color: #007bff;
            font-weight: 500;
        }

        .dropdown-item.selected::before {
            content: "✓";
            color: #007bff;
            font-weight: bold;
        }

        .category {
            padding: 8px 16px;
            background: #f8f9fa;
            font-weight: bold;
            color: #495057;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e9ecef;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .item-count {
            background: #6c757d;
            color: white;
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 12px;
            margin-left: auto;
            flex-shrink: 0;
        }

        .no-results {
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-style: italic;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .card-body-service-seach {
                padding: 15px;
            }
            
            .selected-items {
                padding: 10px;
            }
        }

        /* Animation de hauteur pour la card */
        .card-expanding {
            overflow: hidden;
        }
    </style>
</head>
<body style="padding: 20px; background: #f5f5f5;">
    
    <div class="card-body-service-seach">
        <div class="row">
            <div class="col-lg-12 col-md-12">
                <div class="form-group-enhanced">
                    <label class="form-label-enhanced">
                        <i class="fas fa-vial text-info" style="margin-right: 8px; color: #17a2b8 !important;"></i>
                        Examens Complémentaires
                        <span class="status-badge status-optional ms-2">Optionnel</span>
                    </label>
                    <div class="search-select">
                        <input type="text" class="search-input" placeholder="Rechercher services, examens, médicaments..." id="search-input">
                        <div class="selected-items" id="selected-items"></div>
                        <div class="dropdown-list" id="dropdown-list">
                            <div class="category">Services</div>
                            <div class="dropdown-item" data-value="cardio" data-category="services">Consultation Cardiologue</div>
                            <div class="dropdown-item" data-value="ecg" data-category="services">ECG</div>
                            <div class="dropdown-item" data-value="echo" data-category="services">Échographie</div>
                            
                            <div class="category">Examens Complémentaires</div>
                            <div class="dropdown-item" data-value="bilan" data-category="examens">Bilan Sanguin Complet</div>
                            <div class="dropdown-item" data-value="radio" data-category="examens">Radiographie Thoracique</div>
                            <div class="dropdown-item" data-value="irm" data-category="examens">IRM Cardiaque</div>
                            
                            <div class="category">Packages</div>
                            <div class="dropdown-item" data-value="package-cardio" data-category="packages">Package Cardiologie Complet</div>
                            <div class="dropdown-item" data-value="package-senior" data-category="packages">Package Check-up Senior</div>
                            
                            <div class="category">Prescription Médicamenteuse</div>
                            <div class="dropdown-item" data-value="paracetamol" data-category="medicaments">Paracétamol 1g - 3x/jour</div>
                            <div class="dropdown-item" data-value="amoxicilline" data-category="medicaments">Amoxicilline 500mg - 2x/jour</div>
                            <div class="dropdown-item" data-value="aspirine" data-category="medicaments">Aspirine 100mg - 1x/jour</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('search-input');
        const dropdownList = document.getElementById('dropdown-list');
        const selectedItems = document.getElementById('selected-items');
        const cardBody = document.querySelector('.card-body-service-seach');
        let selected = [];
        let allItems = Array.from(dropdownList.querySelectorAll('.dropdown-item'));

        // Fonction pour animer le redimensionnement de la card
        function animateCardResize() {
            // Forcer un reflow pour que les transitions fonctionnent
            cardBody.style.transition = 'all 0.3s ease';
            
            // La card va automatiquement s'ajuster grâce à height: auto
            setTimeout(() => {
                cardBody.style.transition = '';
            }, 300);
        }

        // Afficher/masquer le dropdown avec animation
        searchInput.addEventListener('focus', () => {
            dropdownList.classList.add('show');
            filterItems('');
        });

        // Filtrer les éléments lors de la saisie
        searchInput.addEventListener('input', (e) => {
            const filter = e.target.value.toLowerCase();
            filterItems(filter);
        });

        function filterItems(filter) {
            let hasVisibleItems = false;
            let currentCategory = null;
            
            // Masquer toutes les catégories d'abord
            dropdownList.querySelectorAll('.category').forEach(cat => {
                cat.style.display = 'none';
            });
            
            allItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                const category = item.dataset.category;
                const isVisible = text.includes(filter);
                
                item.style.display = isVisible ? 'flex' : 'none';
                
                if (isVisible) {
                    hasVisibleItems = true;
                    
                    // Afficher la catégorie si nécessaire
                    if (category !== currentCategory) {
                        const categoryElement = item.previousElementSibling;
                        if (categoryElement && categoryElement.classList.contains('category')) {
                            categoryElement.style.display = 'block';
                        }
                        currentCategory = category;
                    }
                }
            });
            
            // Afficher "Aucun résultat" si nécessaire
            let noResults = dropdownList.querySelector('.no-results');
            if (!hasVisibleItems && filter) {
                if (!noResults) {
                    noResults = document.createElement('div');
                    noResults.className = 'no-results';
                    noResults.textContent = 'Aucun résultat trouvé';
                    dropdownList.appendChild(noResults);
                }
                noResults.style.display = 'block';
            } else if (noResults) {
                noResults.style.display = 'none';
            }
        }

        // Sélectionner un élément
        dropdownList.addEventListener('click', (e) => {
            if (e.target.classList.contains('dropdown-item')) {
                const value = e.target.dataset.value;
                const text = e.target.textContent;
                
                if (!selected.find(item => item.value === value)) {
                    selected.push({value, text});
                    updateSelectedItems();
                    e.target.classList.add('selected');
                    
                    // Animer le redimensionnement de la card
                    animateCardResize();
                }
                
                searchInput.value = '';
                searchInput.focus();
                filterItems('');
            }
        });

        // Mettre à jour l'affichage des éléments sélectionnés
        function updateSelectedItems() {
            selectedItems.innerHTML = selected.map(item => `
                <div class="selected-item">
                    <span>${item.text}</span>
                    <span class="remove-item" data-value="${item.value}">×</span>
                </div>
            `).join('');
            
            // Ajouter un compteur
            updateItemCount();
        }

        function updateItemCount() {
            const existingCount = document.querySelector('.item-count');
            if (existingCount) {
                existingCount.remove();
            }
            
            if (selected.length > 0) {
                const countElement = document.createElement('div');
                countElement.className = 'item-count';
                countElement.textContent = `${selected.length} sélectionné${selected.length > 1 ? 's' : ''}`;
                selectedItems.appendChild(countElement);
            }
        }

        // Supprimer un élément sélectionné
        selectedItems.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-item')) {
                const value = e.target.dataset.value;
                selected = selected.filter(item => item.value !== value);
                updateSelectedItems();
                
                const dropdownItem = dropdownList.querySelector(`[data-value="${value}"]`);
                if (dropdownItem) {
                    dropdownItem.classList.remove('selected');
                }
                
                // Animer le redimensionnement de la card
                animateCardResize();
            }
        });

        // Fermer le dropdown en cliquant ailleurs
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-select')) {
                dropdownList.classList.remove('show');
            }
        });

        // Navigation au clavier
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                dropdownList.classList.remove('show');
                searchInput.blur();
            }
        });

        // Fonctions utiles pour l'intégration
        window.getSelectedValues = function() {
            return selected.map(item => item.value);
        };

        window.setSelectedValues = function(values) {
            selected = [];
            allItems.forEach(item => {
                item.classList.remove('selected');
                if (values.includes(item.dataset.value)) {
                    selected.push({
                        value: item.dataset.value,
                        text: item.textContent
                    });
                    item.classList.add('selected');
                }
            });
            updateSelectedItems();
            animateCardResize();
        };

        // Observer pour détecter les changements de taille
        if (window.ResizeObserver) {
            const resizeObserver = new ResizeObserver(entries => {
                // La card se redimensionne automatiquement
                // Aucune action supplémentaire nécessaire
            });
            resizeObserver.observe(selectedItems);
        }
    </script>
</body>
</html>