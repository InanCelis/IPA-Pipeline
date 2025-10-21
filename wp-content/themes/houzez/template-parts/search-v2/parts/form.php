<?php
/**
 * Advanced Property Search Form - Fully Functional
 */
wp_enqueue_style(
    'search-v2-form-css',
    get_template_directory_uri() . '/template-parts/search-v2/css/form.css',
    array(),
    filemtime(get_template_directory() . '/template-parts/search-v2/css/form.css')
);


?>

<div class="search-filter-bar">
    <!-- Location Search -->
    <div class="filter-item search-input dropdown-container">
        <i class="houzez-icon icon-search mr-1"></i>
        <input type="text" id="location-search" placeholder="Location, Project or Place" autocomplete="off">
        <div class="dropdown-content location-results"></div>
    </div> 
    
    <!-- Property Type -->
    <div class="filter-item dropdown-container" id="types-filter">
        <div class="dropdown-toggle">
            <span class="filter-dropdown">Types</span>
        </div>
        <div class="dropdown-content p-types">
            <div class="dd-form-header">Property Types</div>
            <div class="p-types-grid" id="types-data">
                <label><input type="checkbox" name="type" value="house"> House</label>
                <label><input type="checkbox" name="type" value="apartment"> Apartment</label>
                <label><input type="checkbox" name="type" value="villa"> Villa</label>
                <label><input type="checkbox" name="type" value="condo"> Condo</label>
            </div>
        </div>
    </div>
    
    <!-- Currency -->
    <div class="filter-item dropdown-container" id="currency-filter">
        <div class="dropdown-toggle">
            <span class="filter-dropdown">Currency</span>
        </div>
        <div class="dropdown-content" id="currency-data">
            <div class="currency-option active" data-value="USD">USD</div>
            <div class="currency-option" data-value="EUR">EUR</div>
            <div class="currency-option" data-value="THB">THB</div>
            <div class="currency-option" data-value="IDR">IDR</div>
        </div>
    </div> 
    
    <!-- Price Range -->
    <div class="filter-item dropdown-container" id="price-filter">
        <div class="dropdown-toggle">
            <span class="filter-dropdown">Price</span>
        </div> 
        <div class="dropdown-content price-range-selector">
            <div class="dd-form-header">Price Range</div>
            <div class="price-inputs">
                <div class="price-input">
                    <label>Min</label>
                    <select class="min-price" name="min-price">
                        <option value="">No Min</option>
                        <option value="500000">500,000</option>
                        <option value="1000000">1,000,000</option>
                        <option value="1500000">1,500,000</option>
                        <option value="2000000">2,000,000</option>
                    </select>
                </div>
                <div class="price-input">
                    <label>Max</label>
                    <select class="max-price" name="max-price">
                        <option value="">No Max</option>
                        <option value="1000000">1,000,000</option>
                        <option value="1500000">1,500,000</option>
                        <option value="2000000">2,000,000</option>
                        <option value="2500000">2,500,000</option>
                    </select>
                </div>
                <small class="price-mes"></small>
            </div>
        </div>
    </div>
    
    <!-- Bedrooms -->
    <div class="filter-item dropdown-container" id="beds-filter">
        <div class="dropdown-toggle">
            <span class="filter-dropdown">Bedrooms</span>
        </div>
        <div class="dropdown-content beds-selector">
            <div class="option-group">
                <div class="dd-form-header">Number of Bedrooms</div>
                <div class="option-buttons">
                    <div class="option-button" data-value="">Any</div>
                    <div class="option-button" data-value="1">1+</div>
                    <div class="option-button" data-value="2">2+</div>
                    <div class="option-button" data-value="3">3+</div>
                    <div class="option-button" data-value="4">4+</div>
                    <div class="option-button" data-value="5">5+</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bathrooms -->
    <div class="filter-item dropdown-container" id="baths-filter">
        <div class="dropdown-toggle">
            <span class="filter-dropdown">Bathrooms</span>
        </div>
        <div class="dropdown-content baths-selector">
            <div class="option-group">
                <div class="dd-form-header">Number of Bathrooms</div>
                <div class="option-buttons">
                    <div class="option-button" data-value="">Any</div>
                    <div class="option-button" data-value="1">1+</div>
                    <div class="option-button" data-value="2">2+</div>
                    <div class="option-button" data-value="3">3+</div>
                    <div class="option-button" data-value="4">4+</div>
                    <div class="option-button" data-value="5">5+</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Features -->
    <div class="filter-item dropdown-container" id="features-filter">
        <div class="dropdown-toggle">
            <span class="filter-dropdown">More</span>
        </div>
        <div class="dropdown-content features-selector">
            <div class="option-group">
                <div class="dd-form-header">Features & Amenities</div>
                <div class="feature-grid" id="features-data">
                    <label class="feature-option"><input type="checkbox" name="feature" value="pet-friendly"> Pet Friendly</label>
                    <label class="feature-option"><input type="checkbox" name="feature" value="furnished"> Fully Furnished</label>
                    <label class="feature-option"><input type="checkbox" name="feature" value="wifi"> Wi-Fi</label>
                    <label class="feature-option"><input type="checkbox" name="feature" value="aircon"> Air conditioning</label>
                    <label class="feature-option"><input type="checkbox" name="feature" value="pool"> Swimming Pool</label>
                    <label class="feature-option"><input type="checkbox" name="feature" value="gym"> Gym</label>
                    <label class="feature-option"><input type="checkbox" name="feature" value="garden"> Garden</label>
                    <label class="feature-option"><input type="checkbox" name="feature" value="parking"> Parking</label>
                    <label class="feature-option"><input type="checkbox" name="feature" value="security"> 24 Hour Security</label>
                    <label class="feature-option"><input type="checkbox" name="feature" value="office"> Home Office</label>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reset Button -->
    <button class="reset-button" id="reset-filters">Reset All</button>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.6.2/axios.min.js" integrity="sha512-b94Z6431JyXY14iSXwgzeZurHHRNkLt9d6bAHt7BZT38eqV+GyngIi/tVye4jBKPYQ2lBdRs0glww4fmpuLRwA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<?php
// Enqueue JavaScript
wp_enqueue_script(
    'search-v2-form-js',
    get_template_directory_uri() . '/template-parts/search-v2/js/form.js',
    array('jquery', 'system-script'), // Depends on jQuery and system-script
    filemtime(get_template_directory() . '/template-parts/search-v2/js/form.js'),
    true // Load in footer
);