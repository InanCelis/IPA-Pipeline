<?php

class FormDataController {

    public function __construct() {
        // Register the REST API route when WordPress initializes the REST API
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    // Register the custom route for property types
    public function register_routes() {
        register_rest_route('houzez/v1', '/get-property-types', [
            'methods' => 'GET',
            'callback' => [$this, 'getPropertyTypes'],
            'permission_callback' => '__return_true', // Allow public access
        ]);
    }

    // Callback function for the route
    public function getPropertyTypes() {
        // Simulated property types
        $propertyTypes = [
            'house',
            'apartment',
            'villa',
            'condo'
        ];

        // Return a success response with property types
        return new WP_REST_Response([
            'status' => 'success',
            'message' => 'Property types fetched successfully',
            'data' => $propertyTypes
        ], 200);
    }
}

// Initialize the controller
new FormDataController();

?>
