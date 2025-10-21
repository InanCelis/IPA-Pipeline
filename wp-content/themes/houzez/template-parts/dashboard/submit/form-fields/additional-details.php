<style>
.autocomplete-suggestions {
    border: 1px solid #ddd;
    border-top: none;
    max-height: 200px;
    overflow-y: auto;
    position: absolute;
    background: white;
    z-index: 1000;
    width: calc(100% - 30px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.autocomplete-suggestion {
    padding: 10px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}
.autocomplete-suggestion:hover {
    background-color: #f5f5f5;
}
.autocomplete-suggestion strong {
    font-weight: 600;
}
.position-relative {
    position: relative;
}
</style>

<table class="dashboard-table additional-details-table">
	<thead>
		<tr>
			<td>
				<label><?php echo houzez_option('cl_additional_title', 'Title'); ?></label>
			</td>
			<td>
				<label><?php echo houzez_option('cl_additional_value', 'Value'); ?></label>
			</td>
			<td></td>
			<td></td>
		</tr>
	</thead>
	<tbody id="houzez_additional_details_main">
		<?php
		// Define default fields that should always appear
		$default_fields = array(
			'Owned by',
			'Contact Person',
			'Phone',
			'Email',
			'Website'
		);

		$data_increment = 0;
		$count = 0;
		$existing_features = array();

		if(houzez_edit_property()) {
			global $property_data;
			$additional_features = get_post_meta( $property_data->ID, 'additional_features', true );

			if( !empty($additional_features) ) {
				// Normalize existing features to match default fields (case-insensitive)
				foreach ($additional_features as $feature) {
					$title = isset($feature['fave_additional_feature_title']) ? trim($feature['fave_additional_feature_title']) : '';
					
					// Check if title matches any default field (case-insensitive)
					$matched_default = false;
					foreach ($default_fields as $default_field) {
						if (strtolower($title) === strtolower($default_field)) {
							// Normalize to the default field name
							$feature['fave_additional_feature_title'] = $default_field;
							$matched_default = true;
							break;
						}
					}
					
					$existing_features[] = $feature;
				}
			}
		}

		// First, display default fields (non-removable)
		foreach ($default_fields as $default_title) {
			$existing_value = '';
			$is_owned_by = ($default_title === 'Owned by');
			
			// Check if this default field already has a value in existing data (case-insensitive)
			if (!empty($existing_features)) {
				foreach ($existing_features as $feature) {
					if (isset($feature['fave_additional_feature_title'])) {
						$feature_title = trim($feature['fave_additional_feature_title']);
						if (strtolower($feature_title) === strtolower($default_title)) {
							$existing_value = isset($feature['fave_additional_feature_value']) ? $feature['fave_additional_feature_value'] : '';
							break;
						}
					}
				}
			}
			?>
			<tr class="default-field">
				<td class="table-half-width">
					<input class="form-control" name="additional_features[<?php echo esc_attr( $count ); ?>][fave_additional_feature_title]" placeholder="<?php echo houzez_option('cl_additional_title_plac', 'Eg: Equipment' ); ?>" type="text" value="<?php echo esc_attr($default_title); ?>" readonly>
				</td>
				<td class="table-half-width <?php echo $is_owned_by ? 'position-relative' : ''; ?>">
					<input class="form-control <?php echo $is_owned_by && current_user_can('administrator') ? 'company-autocomplete' : ''; ?>" 
						name="additional_features[<?php echo esc_attr( $count ); ?>][fave_additional_feature_value]" 
						placeholder="<?php echo $is_owned_by ? 'Start typing company name...' : 'Enter Value'; ?>" 
						type="text" 
						value="<?php echo esc_attr($existing_value); ?>" 
						data-row-index="<?php echo esc_attr( $count ); ?>"
						autocomplete="off" required>
					<?php if ($is_owned_by): ?>
					<div class="autocomplete-suggestions" id="suggestions-<?php echo esc_attr( $count ); ?>" style="display:none;"></div>
					<?php endif; ?>
				</td>
				<td class="">
					<a class="sort-additional-row btn btn-light-grey-outlined"><i class="houzez-icon icon-navigation-menu"></i></a>
				</td>
				<td>
					<!-- No remove button for default fields -->
				</td>
			</tr>
			<?php
			$count++;
		}

		// Then, display any additional custom fields that aren't in the default list
		if(houzez_edit_property() && !empty($existing_features)) {
			foreach ($existing_features as $add_feature) {
				$add_title = isset($add_feature['fave_additional_feature_title']) ? trim($add_feature['fave_additional_feature_title']) : '';
				$add_value = isset($add_feature['fave_additional_feature_value']) ? $add_feature['fave_additional_feature_value'] : '';
				
				// Skip if this is already displayed as a default field (case-insensitive comparison)
				$is_default = false;
				foreach ($default_fields as $default_field) {
					if (strtolower($add_title) === strtolower($default_field)) {
						$is_default = true;
						break;
					}
				}
				
				if ($is_default) {
					continue;
				}
				?>
				<tr>
					<td class="table-half-width">
						<input class="form-control" name="additional_features[<?php echo esc_attr( $count ); ?>][fave_additional_feature_title]" placeholder="<?php echo houzez_option('cl_additional_title_plac', 'Eg: Equipment' ); ?>" type="text" value="<?php echo esc_attr($add_title); ?>">
					</td>
					<td class="table-half-width">
						<input class="form-control" name="additional_features[<?php echo esc_attr( $count ); ?>][fave_additional_feature_value]" placeholder="Enter Value" type="text" value="<?php echo esc_attr($add_value); ?>">
					</td>
					<td class="">
						<a class="sort-additional-row btn btn-light-grey-outlined"><i class="houzez-icon icon-navigation-menu"></i></a>
					</td>
					<td>
						<button data-remove="<?php echo esc_attr( $count ); ?>" class="remove-additional-row btn btn-light-grey-outlined"><i class="houzez-icon icon-close"></i></button>
					</td>
				</tr>
				<?php
				$count++;
			}
		}

		$data_increment = $count - 1;
		?>
	</tbody>
    <tfoot>
		<tr>
			<td colspan="4">
				<button data-increment="<?php echo esc_attr($data_increment); ?>" class="add-additional-row btn btn-primary btn-left-icon mt-2"><i class="houzez-icon icon-add-circle"></i> <?php esc_html_e( 'Add New', 'houzez' ); ?></button>
			</td>
		</tr>
	</tfoot>
</table>

<script>
jQuery(document).ready(function($) {
    let searchTimeout;
    
    // Handle autocomplete for company name
    $(document).on('input', '.company-autocomplete', function() {
        const $input = $(this);
        const searchTerm = $input.val();
        const rowIndex = $input.data('row-index');
        const $suggestions = $('#suggestions-' + rowIndex);
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        if (searchTerm.length < 2) {
            $suggestions.hide().html('');
            return;
        }
        
        // Debounce search
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'search_partnerships',
                    search: searchTerm,
                    nonce: '<?php echo wp_create_nonce("search_partnerships_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        let html = '';
                        response.data.forEach(function(company) {
                            html += '<div class="autocomplete-suggestion" data-company=\'' + JSON.stringify(company) + '\'>';
                            html += '<strong>' + company.company_name + '</strong><br>';
                            html += '<small>' + (company.contact_person || '') + ' - ' + (company.mobile || '') + '</small>';
                            html += '</div>';
                        });
                        $suggestions.html(html).show();
                    } else {
                        $suggestions.html('<div class="autocomplete-suggestion">No results found</div>').show();
                    }
                }
            });
        }, 300);
    });
    
    // Handle suggestion click
    $(document).on('click', '.autocomplete-suggestion', function() {
        const companyData = $(this).data('company');
        
        if (!companyData) return;
        
        const $row = $(this).closest('tr');
        const rowIndex = $(this).closest('.position-relative').find('input').data('row-index');
        
        // Find all input fields in default fields section
        $('tbody#houzez_additional_details_main tr.default-field').each(function() {
            const $titleInput = $(this).find('input[name*="[fave_additional_feature_title]"]');
            const $valueInput = $(this).find('input[name*="[fave_additional_feature_value]"]');
            const title = $titleInput.val();
            
            switch(title) {
                case 'Owned by':
                    $valueInput.val(companyData.company_name);
                    break;
                case 'Contact Person':
                    $valueInput.val(companyData.contact_person || '');
                    break;
                case 'Phone':
                    $valueInput.val(companyData.mobile || '');
                    break;
                case 'Email':
                    $valueInput.val(companyData.email || '');
                    break;
                case 'Website':
                    $valueInput.val(companyData.website || '');
                    break;
            }
        });
        
        // Hide suggestions
        $('.autocomplete-suggestions').hide();
    });
    
    // Hide suggestions when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).hasClass('company-autocomplete')) {
            $('.autocomplete-suggestions').hide();
        }
    });
});
</script>	