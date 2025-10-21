jQuery(document).ready(function($) {

    // Sample location data - replace with your actual data
    const locationData = [
        { name: "Bang Tao", value: "bang-tao", category: "Beach", subgroup: "Jomtien", tag: "BEACH" },
        { name: "Central Festival Pattaya Beach", value: "central-festival", category: "Shopping", subgroup: "Laguna", tag: "SHOPPING" },
        { name: "Mai Kao", value: "mai-kao", category: "Beach", subgroup: "Mai Kao", tag: "BEACH" }
    ];

    // Initialize all functionality
    function initSearchFilters() {
        initDropdownToggles();
        initLocationSearch();
        initPriceRange();
        initBedBathSelectors();
        initResetButton();
        initFromUrl();
        
        //api
        initGetPropertyTypes();
        initGetCurrency();
        initGetPropertyFeatures();
    }



    var apiUrl  = 'https://internationalpropertyalerts.com/wp-json/houzez-search-api/v1';

    //get property type
    function initGetPropertyTypes() {
        let path = apiUrl + '/get-property-types'; 
        $.SystemScript.executeGet(path).done((response) => {
            if (response.data.status === 'success' && response.data.data.length > 0) {
                var gridHTML = response.data.data.map(t => 
                    `<label><input type="checkbox" name="type" value="${t.slug}"> ${t.name}</label>`
                ).join('');

                $('#types-data').html(gridHTML);
                initPropertyTypes();
                initFromUrl();
            } else {
                console.log(response.data);
            }
        })
        .fail((jqXHR, textStatus, errorThrown) => {
            console.error('AJAX Request Failed:', textStatus, errorThrown);
        });
    }

    //get currency
    function initGetCurrency() {
        let path = apiUrl + '/get-currency'; 
        $.SystemScript.executeGet(path).done((response) => {
            if (response.data.status === 'success' && response.data.data.length > 0) {
                var gridHTML = response.data.data.map((c) => 
                    `<div class="currency-option" data-value="${c.currency}">${c.currency}</div>`
                ).join('');

                $('#currency-data').html(gridHTML);
                initCurrencySelector();
                initFromUrl();
            } else {
                console.log(response.data);
            }
        })
        .fail((jqXHR, textStatus, errorThrown) => {
            console.error('AJAX Request Failed:', textStatus, errorThrown);
        });
    }


    //get property features
    function initGetPropertyFeatures() {
        let path = apiUrl + '/get-features'; 
        $.SystemScript.executeGet(path).done((response) => {
            console.log(response.data)
            if (response.data.status === 'success' && response.data.data.length > 0) {
                var gridHTML = response.data.data.map(f => 
                    `<label class="feature-option"><input type="checkbox" name="feature" value="${f.slug}"> ${f.name}</label>`
                ).join('');

                $('#features-data').html(gridHTML);
                initFeaturesSelector();
                initFromUrl();
            } else {
                console.log(response.data);
            }
        })
        .fail((jqXHR, textStatus, errorThrown) => {
            console.error('AJAX Request Failed:', textStatus, errorThrown);
        });
    }



   


    // Initialize dropdown toggle functionality for all filters
    function initDropdownToggles() {
        $('.dropdown-toggle').on('click', function(e) {
            e.stopPropagation();
            const $container = $(this).closest('.dropdown-container');
            
            // Close all other dropdowns
            $('.dropdown-container').not($container).removeClass('active');
            
            // Toggle current dropdown
            $container.toggleClass('active');
        });

        // Close dropdowns when clicking outside
        hidingContainer();
    }

    

    // 1. Location Search with Dynamic Dropdown
    function initLocationSearch() {
        const $searchInput = $('#location-search');
        const $dropdown = $('.location-results');
        const $searchContainer = $('.search-input');
        
        $searchInput.on('input', function() {
            alert('ddd')
            const searchTerm = $(this).val().toLowerCase();
            populateLocationDropdown(searchTerm);
            $dropdown.show();
        });

        $searchInput.on('focus', function() {
            hidingContainer();
        });

        
        // Hide dropdown when clicking outside or mouse leaves
        $(document).on('click', function(e) {
            if (!$(e.target).closest($searchContainer).length) {
                $dropdown.hide();
            }
        });

        // Hide dropdown when mouse leaves the search container
        $searchContainer.on('mouseleave', function() {
            // Only hide if not focused (to prevent hiding while typing)
            if (!$searchInput.is(':focus')) {
                $dropdown.hide();
            }
        });

        // Filter locations on input
        $searchInput.on('input change', function() {
            const searchTerm = $(this).val().toLowerCase();
            populateLocationDropdown(searchTerm);
            hidingContainer();
        });

        // Handle location selection
        $(document).on('click', '.location-option', function() {
            const $option = $(this);
            const value = $option.data('value');
            const text = $option.text().replace(/ BEACH$| SHOPPING$/, '').trim();
            
            $searchInput.val(text);
            $dropdown.hide();
            updateUrlParam('location', value);
            
            // Update active state
            $('.location-option').removeClass('active');
            $option.addClass('active');
        });
    }

    function populateLocationDropdown(filter = '') {
        const $dropdown = $('.location-results');
        let html = '';
        const filteredData = filter ? 
            locationData.filter(loc => loc.name.toLowerCase().includes(filter.toLowerCase())) : 
            locationData;

        // Group by category
        const categories = [...new Set(filteredData.map(item => item.category))];
        
        categories.forEach(category => {
            html += `<div class="location-group">
                <div class="location-category">${category}</div>`;
            
            // Group by subgroup within category
            const subgroups = [...new Set(filteredData
                .filter(item => item.category === category)
                .map(item => item.subgroup))];
            
            subgroups.forEach(subgroup => {
                const items = filteredData.filter(item => 
                    item.category === category && item.subgroup === subgroup);
                
                if (items.length) {
                    html += `<div class="location-subgroup">
                        <div class="subgroup-header">${subgroup}</div>`;
                    
                    items.forEach(item => {
                        const isActive = getUrlParam('location') === item.value;
                        html += `<div class="location-option ${isActive ? 'active' : ''}" data-value="${item.value}">
                            ${item.name} <span class="location-tag">${item.tag}</span>
                        </div>`;
                    });
                    
                    html += `</div>`;
                }
            });
            
            html += `</div>`;
        });

        $dropdown.html(html);
    }

    // 2. Currency Selector
    function initCurrencySelector() {
        const $currencyOptions = $('.currency-option');
        
        $currencyOptions.on('click', function() {
            const $option = $(this);
            const currency = $option.data('value');
            
            $currencyOptions.removeClass('active');
            $option.addClass('active');
            
            $('#currency-filter .filter-dropdown').text(currency);
            updateUrlParam('currency', currency);
        });
    }

    // 3. Price Range
    function initPriceRange() {
        const $minPrice = $('.min-price');
        const $maxPrice = $('.max-price');
        const $priceDropdown = $('#price-filter .filter-dropdown');

        $minPrice.add($maxPrice).on('change', function() {
            $('.price-mes').text('');
            const min = $minPrice.val();
            const max = $maxPrice.val();
            
            // Validate range
            if (min && max && parseInt(min) > parseInt(max)) {
                $('.price-mes').text('Minimum price cannot be greater than maximum price');
                $(this).val('');
                return;
            }
            
            // Update display text
            if (min || max) {
                const displayText = (min ? formatCurrency(min) : 'No Min') + ' - ' + 
                                   (max ? formatCurrency(max) : 'No Max');
                $priceDropdown.text(displayText);
            } else {
                $priceDropdown.text('Price');
            }
            
            // Update URL
            updateUrlParam('min-price', min);
            updateUrlParam('max-price', max);
        });
    }

    // 4. Bed/Bath Selectors
    function initBedBathSelectors() {
        $('.beds-selector .option-button, .baths-selector .option-button').on('click', function() {
            const $button = $(this);
            const $container = $button.closest('.dropdown-content');
            const type = $container.hasClass('beds-selector') ? 'bedrooms' : 'bathrooms';
            const value = $button.data('value');
            
            // Update UI
            $container.find('.option-button').removeClass('active');
            $button.addClass('active');
            
            // Update button text
            $(`#${type}-filter .filter-dropdown`)
                .text(type === 'bedrooms' ? 'Bedrooms' + (value ? ` ${value}+` : '') : 
                                        'Bathrooms' + (value ? ` ${value}+` : ''));
            
            // Update URL
            updateUrlParam(type, value);
        });
    }

    // 5. Features Selector
    function initFeaturesSelector() {
        const $featureCheckboxes = $('#features-filter input[name="feature"]');
        const $featuresDropdown = $('#features-filter .filter-dropdown');

        $featureCheckboxes.on('change', function() {
            const features = [];
            $featureCheckboxes.filter(':checked').each(function() {
                features.push($(this).val());
            });
            
            // Update URL with all selected features
            const currentParams = new URLSearchParams(window.location.search);
            currentParams.delete('feature');
            
            features.forEach(feature => {
                currentParams.append('feature', feature);
            });
            
            history.pushState(null, null, '?' + currentParams.toString());
            
            // Update More button text
            const count = features.length;
            $featuresDropdown.text('More' + (count ? ` (${count})` : ''));
        });
    }

    // 6. Property Types
    function initPropertyTypes() {
        const $typeCheckboxes = $('#types-filter input[name="type"]');
        const $typesDropdown = $('#types-filter .filter-dropdown');

        $typeCheckboxes.on('change', function() {
            const types = [];
            $typeCheckboxes.filter(':checked').each(function() {
                types.push($(this).val());
            });
            
            // Update URL with all selected types
            const currentParams = new URLSearchParams(window.location.search);
            currentParams.delete('type');
            
            types.forEach(type => {
                currentParams.append('type', type);
            });
            
            history.pushState(null, null, '?' + currentParams.toString());
            
            // Update Types button text
            const count = types.length;
            $typesDropdown.text('Types' + (count ? ` (${count})` : ''));
        });
    }

    // 7. Reset Button
    function initResetButton() {
        $('#reset-filters').on('click', function() {
            // Reset form elements
            $('#location-search').val('');
            $('input[type="checkbox"]').prop('checked', false);
            $('select').val('');
            $('.option-button').removeClass('active');
            $('.location-option').removeClass('active');
            $('.currency-option').removeClass('active').first().addClass('active');
            
            // Reset dropdown texts
            $('#types-filter .filter-dropdown').text('Types');
            $('#price-filter .filter-dropdown').text('Price');
            $('#beds-filter .filter-dropdown').text('Bedrooms');
            $('#baths-filter .filter-dropdown').text('Bathrooms');
            $('#features-filter .filter-dropdown').text('More');
            $('#currency-filter .filter-dropdown').text('Currency');

            hidingContainer();

            
            // Clear URL parameters
            history.pushState(null, null, window.location.pathname);
        });
    }

    // Initialize with current URL parameters
    function initFromUrl() {
        // Location
        const locationParam = getUrlParam('location');
        if (locationParam) {
            const locData = locationData.find(l => l.value === locationParam);
            if (locData) {
                $('#location-search').val(locData.name);
                $(`.location-option[data-value="${locationParam}"]`).addClass('active');
            }
        }
        
        // Currency
        const currencyParam = getUrlParam('currency') || '';
        $(`.currency-option[data-value="${currencyParam}"]`).click();
        
        // Price range
        const minPrice = getUrlParam('min-price');
        const maxPrice = getUrlParam('max-price');
        if (minPrice) $('.min-price').val(minPrice).trigger('change');
        if (maxPrice) $('.max-price').val(maxPrice).trigger('change');
        
        // Beds
        const bedsParam = getUrlParam('bedrooms');
        if (bedsParam !== null) {
            $(`.beds-selector .option-button[data-value="${bedsParam}"]`).click();
        }
        
        // Baths
        const bathsParam = getUrlParam('bathrooms');
        if (bathsParam !== null) {
            $(`.baths-selector .option-button[data-value="${bathsParam}"]`).click();
        }
        
        // Features
        const featuresParam = getAllUrlParams('feature');
        featuresParam.forEach(feature => {
            $(`#features-filter input[name="feature"][value="${feature}"]`).prop('checked', true);
        });
        if (featuresParam.length) {
            $('#features-filter .filter-dropdown').text('More' + (featuresParam.length ? ` (${featuresParam.length})` : ''));
        }
        
        // Types
        const typesParam = getAllUrlParams('type');
        typesParam.forEach(type => {
            $(`#types-filter input[name="type"][value="${type}"]`).prop('checked', true);
        });
        if (typesParam.length) {
            $('#types-filter .filter-dropdown').text('Types' + (typesParam.length ? ` (${typesParam.length})` : ''));
        }
    }

    // Helper functions
    function getUrlParam(key) {
        const params = new URLSearchParams(window.location.search);
        return params.get(key);
    }

    function getAllUrlParams(key) {
        const params = new URLSearchParams(window.location.search);
        return params.getAll(key);
    }

    function updateUrlParam(key, value) {
        const currentParams = new URLSearchParams(window.location.search);
        
        if (value) {
            currentParams.set(key, value);
        } else {
            currentParams.delete(key);
        }
        
        history.pushState(null, null, '?' + currentParams.toString());
    }

    function formatCurrency(amount) {
        if (!amount) return '';
        return parseFloat(amount).toLocaleString('en-US');
    }

    function hidingContainer() {
        const $container = $(this).closest('.dropdown-container');            
        $('.dropdown-container').not($container).removeClass('active');
    }

    // Initialize everything
    initSearchFilters();
});