<div class="property-form-tabs-wrap">
	<div class="property-form-tabs">
        <ul class="nav nav-tabs">
        	<li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#tab_tour" role="tab"><span class="tab-title"><?php esc_html_e('Schedule a tour', 'houzez'); ?></span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab_agent_form" role="tab"><span class="tab-title"><?php esc_html_e('Request Info', 'houzez'); ?></span></a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="property-form-tabs-tab-pane tab-pane fade show active" id="tab_tour" role="tabpanel">
            	<?php get_template_part('property-details/partials/schedule-tour-sidebar-form'); ?>
            </div>
            <div class="property-tabs-module-tab-pane tab-pane fade" id="tab_agent_form" role="tabpanel">
                
                <?php get_template_part('property-details/agent-form'); ?>

            </div>
        </div>
    </div><!-- property-form-tabs -->
</div><!-- property-form-tabs-wrap -->