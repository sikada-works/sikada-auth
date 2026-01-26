<?php

namespace SikadaWorks\SikadaAuth\API;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Example REST API Controller
 *
 * Demonstrates REST API endpoint registration and handling.
 *
 * @since 1.0.0
 */
class ExampleController extends WP_REST_Controller
{
	/**
	 * REST API namespace
	 *
	 * @var string
	 */
	protected $namespace = 'sikada-auth/v1';

	/**
	 * REST API base route
	 *
	 * @var string
	 */
	protected $rest_base = 'items';

	/**
	 * Register REST routes
	 *
	 * @since 1.0.0
	 */
	public function register_routes()
	{
		// GET /items - Get all items
		register_rest_route($this->namespace, '/' . $this->rest_base, [
			[
				'methods' => WP_REST_Server::READABLE,
				'callback' => [$this, 'get_items'],
				'permission_callback' => [$this, 'check_permission'],
			],
		]);

		// GET /items/{id} - Get single item
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
			[
				'methods' => WP_REST_Server::READABLE,
				'callback' => [$this, 'get_item'],
				'permission_callback' => [$this, 'check_permission'],
				'args' => [
					'id' => [
						'validate_callback' => function ($param) {
							return is_numeric($param);
						},
					],
				],
			],
		]);

		// POST /items - Create item
		register_rest_route($this->namespace, '/' . $this->rest_base, [
			[
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => [$this, 'create_item'],
				'permission_callback' => [$this, 'check_permission'],
				'args' => $this->get_create_args(),
			],
		]);
	}

	/**
	 * Get all items
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error Response object or error
	 */
	public function get_items($request)
	{
		// Example: Apply filters to allow modification
		$items = apply_filters('sikada_auth_api_items', [
			['id' => 1, 'title' => 'Example Item 1'],
			['id' => 2, 'title' => 'Example Item 2'],
		]);

		return new WP_REST_Response($items, 200);
	}

	/**
	 * Get single item
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error Response object or error
	 */
	public function get_item($request)
	{
		$id = $request->get_param('id');

		// Example item retrieval
		$item = [
			'id' => $id,
			'title' => 'Example Item ' . $id,
		];

		// Fire action hook
		do_action('sikada_auth_before_get_item', $id);

		return new WP_REST_Response($item, 200);
	}

	/**
	 * Create item
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error Response object or error
	 */
	public function create_item($request)
	{
		$title = sanitize_text_field($request->get_param('title'));

		// Validate
		if (empty($title)) {
			return new WP_Error(
				'invalid_title',
				__('Title is required', 'sikada-auth'),
				['status' => 400]
			);
		}

		// Fire pre-create action
		do_action('sikada_auth_before_create_item', $title);

		// Create item (example)
		$item = [
			'id' => rand(1, 1000),
			'title' => $title,
		];

		// Fire post-create action
		do_action('sikada_auth_after_create_item', $item['id'], $item);

		return new WP_REST_Response($item, 201);
	}

	/**
	 * Check permission
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object
	 * @return bool|WP_Error True if allowed, WP_Error otherwise
	 */
	public function check_permission($request)
	{
		// Example: Allow only authenticated users
		if (!is_user_logged_in()) {
			return new WP_Error(
				'rest_forbidden',
				__('You must be logged in to access this endpoint', 'sikada-auth'),
				['status' => 403]
			);
		}

		// Allow filtering permission check
		return apply_filters('sikada_auth_api_permission', true, $request);
	}

	/**
	 * Get create item arguments
	 *
	 * @since 1.0.0
	 * @return array Arguments schema
	 */
	private function get_create_args()
	{
		return [
			'title' => [
				'required' => true,
				'type' => 'string',
				'description' => __('Item title', 'sikada-auth'),
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}
}
