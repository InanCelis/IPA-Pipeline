// jQuery(document).ready(function($) {

//     function getURLParam(name) {
//         const urlParams = new URLSearchParams(window.location.search);
//         const values = urlParams.getAll(name);
//         return values.length ? values : null;
//     }

//     function loadCitiesForCountry(countrySlug, countryName, $citySelect) {
        
// 	    const locationParam = getURLParam('location[]');
//         $citySelect.prop('disabled', true).empty();
//         $citySelect.append('<option value="">Loading cities...</option>');
        
//         $citySelect.selectpicker('refresh');

//         if (countrySlug === "") {
//             // Get all cities from local WP
//             $.ajax({
//                 url: houzez_ajax_object.ajax_url,
//                 type: 'POST',
//                 dataType: 'json',
//                 data: { action: 'houzez_get_all_cities' },
//                 success: function(response) {
//                     $citySelect.empty().append('<option value="">' + houzez_ajax_object.all_cities_text + '</option>');
// 		            let buttonText = "All Cities"
//                     let excuteStyleChages = false;
//                     $.each(response, function(index, city) {
//                         let selected = false;
//                         if (locationParam && locationParam.includes(city.slug)) {
//                             selected = true;
//                             excuteStyleChages = true;
//                             buttonText = city.name;
//                         }
//                         $citySelect.append(`<option value="${city.slug}" ${selected ? 'selected="selected"' : ''}> ${city.name} </option>`);
//                     });
//                     $citySelect.prop('disabled', false).selectpicker('refresh');
                    
//                     if(excuteStyleChages) {
//                         $('.houzezCityFilter').parent().find('button').removeClass('bs-placeholder');
//                         $('.houzezCityFilter').parent().find('.filter-option-inner-inner').html(buttonText);
//                     }
                    
//                 },
//                 error: function() {
//                     $citySelect.empty().append('<option value="">Failed to load cities</option>');
//                     $citySelect.prop('disabled', false).selectpicker('refresh');
//                 }
//             });
//         } else {
//             // Get cities from external API
//             $.ajax({
//                 url: 'https://countriesnow.space/api/v0.1/countries/cities',
//                 type: 'POST',
//                 dataType: 'json',
//                 contentType: 'application/json',
//                 data: JSON.stringify({ country: countryName }),
//                 success: function(response) {
//                     $citySelect.empty().append('<option value="">' + houzez_ajax_object.all_cities_text + '</option>');
//                     let buttonText = "All Cities"
//                     let selected = false;
//                     let excuteStyleChages = false;
//                     if (!response.error && response.data.length > 0) {
//                         $.each(response.data, function(index, cityName) {
//                             var citySlug = cityName.toLowerCase().replace(/\s+/g, '-');
                            
//                             if (locationParam && locationParam.includes(citySlug)) {
//                                 selected = true;
//                                 buttonText = cityName;
//                                 excuteStyleChages = true;
//                             }
//                             $citySelect.append(`<option value="${citySlug}" ${selected ? 'selected="selected"' : ''}> ${cityName} </option>`);
//                         });
//                     } else {
//                         $citySelect.append('<option value="">No cities found</option>');
//                     }
//                     $citySelect.prop('disabled', false).selectpicker('refresh');
//                     if(excuteStyleChages) { 
//                         $('.houzezCityFilter').parent().find('button').removeClass('bs-placeholder');
//                         $('.houzezCityFilter').parent().find('.filter-option-inner-inner').html(buttonText);
//                     } 
//                 },
//                 error: function() {
//                     $citySelect.empty().append('<option value="">Error loading cities</option>');
//                     $citySelect.prop('disabled', false).selectpicker('refresh');
//                 }
//             });
//         }
//     }

//     // 🔁 Load cities when user changes country
//     $('.houzez-country-js').on('change', function() {
//         var countrySlug = $(this).val();
// 	var countryName = $(this).find("option:selected").text().trim();
// 	var $citySelect = $('.houzez-city-js select');
//         loadCitiesForCountry(countrySlug, countryName, $citySelect);
//     });

//     // ✅ Check if country has a selected option on page load
//     var $countrySelect = $('.houzez-country-js');
//     var selectedValue = $countrySelect.val();
//     if (selectedValue && selectedValue !== "") {
//         var countryName = $countrySelect.find("option:selected").text().trim();
// 	var $citySelect = $('.houzez-city-js');
//         loadCitiesForCountry(selectedValue, countryName, $citySelect);
//     }
// });



jQuery(document).ready(function($) {

    function getURLParam(name) {
        const urlParams = new URLSearchParams(window.location.search);
        const values = urlParams.getAll(name);
        return values.length ? values : null;
    }

    function loadCitiesForCountry(countrySlug, countryName, $citySelect) {
        
        const locationParam = getURLParam('location[]');
        $citySelect.prop('disabled', true).empty();
        $citySelect.append('<option value="">Loading cities...</option>');
        
        $citySelect.selectpicker('refresh');

        if (countrySlug === "") {
            // Get all cities from local WP
            $.ajax({
                url: houzez_ajax_object.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: { 
                    action: 'houzez_get_all_cities',
                    nonce: houzez_ajax_object.nonce
                },
                success: function(response) {
                    populateCitySelect($citySelect, response, locationParam);
                },
                error: function() {
                    $citySelect.empty().append('<option value="">Failed to load cities</option>');
                    $citySelect.prop('disabled', false).selectpicker('refresh');
                }
            });
        } else {
            // Get cities from external API first, then filter server-side
            $.ajax({
                url: 'https://countriesnow.space/api/v0.1/countries/cities',
                type: 'POST',
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify({ country: countryName }),
                success: function(response) {
                    if (!response.error && response.data && response.data.length > 0) {
                        // Send API cities to server for filtering against database
                        $.ajax({
                            url: houzez_ajax_object.ajax_url,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'houzez_get_cities_by_country',
                                country: countryName,
                                api_cities: JSON.stringify(response.data),
                                nonce: houzez_ajax_object.nonce
                            },
                            success: function(filterResponse) {
                                if (filterResponse.success) {
                                    populateCitySelect($citySelect, filterResponse.data, locationParam);
                                } else {
                                    $citySelect.empty().append('<option value="">No matching cities found</option>');
                                    $citySelect.prop('disabled', false).selectpicker('refresh');
                                }
                            },
                            error: function() {
                                $citySelect.empty().append('<option value="">Error filtering cities</option>');
                                $citySelect.prop('disabled', false).selectpicker('refresh');
                            }
                        });
                    } else {
                        $citySelect.empty().append('<option value="">No cities found for this country</option>');
                        $citySelect.prop('disabled', false).selectpicker('refresh');
                    }
                },
                error: function() {
                    $citySelect.empty().append('<option value="">Error loading cities from API</option>');
                    $citySelect.prop('disabled', false).selectpicker('refresh');
                }
            });
        }
    }

    function populateCitySelect($citySelect, cities, locationParam) {
        $citySelect.empty().append('<option value="">' + houzez_ajax_object.all_cities_text + '</option>');
        
        let buttonText = "All Cities";
        let excuteStyleChages = false;
        
        if (cities && cities.length > 0) {
            // Sort cities alphabetically
            cities.sort((a, b) => a.name.localeCompare(b.name));
            
            $.each(cities, function(index, city) {
                let selected = false;
                if (locationParam && locationParam.includes(city.slug)) {
                    selected = true;
                    excuteStyleChages = true;
                    buttonText = city.name;
                }
                $citySelect.append(`<option value="${city.slug}" ${selected ? 'selected="selected"' : ''}>${city.name}</option>`);
            });
        } else {
            $citySelect.append('<option value="">No cities available</option>');
        }
        
        $citySelect.prop('disabled', false).selectpicker('refresh');
        
        if (excuteStyleChages) {
            $('.houzezCityFilter').parent().find('button').removeClass('bs-placeholder');
            $('.houzezCityFilter').parent().find('.filter-option-inner-inner').html(buttonText);
        }
    }

    // 🔁 Load cities when user changes country
    $('.houzez-country-js').on('change', function() {
        var countrySlug = $(this).val();
        var countryName = $(this).find("option:selected").text().trim();
        var $citySelect = $('.houzez-city-js select');
        loadCitiesForCountry(countrySlug, countryName, $citySelect);
    });

    // ✅ Check if country has a selected option on page load
    var $countrySelect = $('.houzez-country-js');
    var selectedValue = $countrySelect.val();
    if (selectedValue && selectedValue !== "") {
        var countryName = $countrySelect.find("option:selected").text().trim();
        var $citySelect = $('.houzez-city-js');
        loadCitiesForCountry(selectedValue, countryName, $citySelect);
    }
});