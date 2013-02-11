<?php
/*
Plugin Name: Conductrics
Description: Conductrics tests for WordPress
Version 1.0
Author: 9seeds, LLC
License: GPLv2 or later
*/

/*
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
    02111-1307 USA or see <http://www.gnu.org/licenses/>.
*/

define( 'CONDUCTRICS_PLUGIN_URL', plugins_url( '/',  __FILE__ ) );
define( 'CONDUCTRICS_PLUGIN_DIR', dirname( __FILE__ ) . '/' );
define( 'CONDUCTRICS_DEBUG', false );

class Conductrics {

	private $post_override;
	private $api_key;
	private $owner;
	private $api_url = 'http://api.conductrics.com/';

	public function onInit() {
		if ( is_admin() ) {
			require_once CONDUCTRICS_PLUGIN_DIR . 'conductrics_admin.php';
			$conductrics_admin = new Conductrics_Admin();
		} else {
			$this->api_key = get_option( 'conductrics_apikey' );
			$this->owner = get_option( 'conductrics_owner' );
			if ( $this->api_key && $this->owner )
				add_action( 'template_redirect',  array( $this, 'onTemplateRedirect' ) );
		}
	}

	public function onTemplateRedirect() {
		$post_id = NULL;
		$post = get_post( $post_id );
		if ( empty( $post ) )
			return;
		
		$agent = get_post_meta( $post->ID, 'conductrics_test', true );
		if ( ! empty( $agent ) ) {
			//@see http://www.ebrueggeman.com/blog/wordpress-relnext-and-firefox-prefetching
			remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );

			//tell conductrics about our options and goals
			$options = $agent['options'];
			$option_ids = array_keys( $options );
			array_walk( $option_ids, array( $this, 'addPostType' ), $post->post_type );
			$response = $this->get( "agent-{$post->ID}/decision/" . implode( ',',  $option_ids ) );

			if ( isset( $response['response']['code'] ) && $response['response']['code'] == 200 &&
				 ( $json_response = json_decode( $response['body'] ) ) ) {

				//set a session cookie
				@setcookie( 'conductrics-session', $json_response->session, 0, COOKIEPATH, COOKIE_DOMAIN );

				$option_parts = explode( '_', $json_response->decision );
				//remember the post we should show instead
				$this->post_override = get_post( $option_parts[1] );
				//add_filter( 'single_post_title', array( $this, 'filterSingleTitle' ) ); //this can override the HTML page title
				add_action( 'the_post', array( $this, 'onThePost' ) );
			}
		} elseif ( ( $value = get_post_meta( $post->ID, 'conductrics_goal', true ) ) != '' && isset( $_COOKIE['conductrics-session'] ) ) {
			//GOOOOOOOOOOOOOAAAAAAALLLL! (maybe move this to 'shutdown' action)
			$parent_id =  get_post_meta( $post->ID, 'conductrics_test_parent', true );
			$result = $this->post( "agent-{$parent_id}/goal/{$post->post_type}_{$post->ID}?reward={$value}" );
		}
	}
		
	public function addPostType( &$id, $key, $post_type ) {
		$id = "{$post_type}_{$id}";
	}
	
	public function filterSingleTitle( $title ) {
		return $this->post_override->post_title;
	}
	
	public function onThePost() {
		add_filter( 'the_title', array( $this, 'filterTitle' ) );
		add_filter( 'the_content', array( $this, 'filterContent' ) );
	}

	public function filterTitle( $title ) {
		//run this filter exactly once
		remove_filter( 'the_title', array( $this, 'filterTitle' ) );
		return $this->post_override->post_title;
	}

	public function filterContent( $content ) {
		//run this filter exactly once
		remove_filter( 'the_content', array( $this, 'filterContent' ) );
		return $this->post_override->post_content;
	}

	private function getHeaders() {
		$headers = array( 'x-mpath-apikey' => $this->api_key );
		if ( isset( $_COOKIE['conductrics-session'] ) )
			$headers['x-mpath-session'] = $_COOKIE['conductrics-session'];
		return $headers;
	}

	private function get( $URI ) {
		$url = "{$this->api_url}{$this->owner}/{$URI}";
		$args = array(
			'headers' => $this->getHeaders(),
		);
		$result = wp_remote_get( $url, $args );
		if ( CONDUCTRICS_DEBUG )
			file_put_contents( '/tmp/get.txt', print_r( $args, true ) . PHP_EOL . $url . PHP_EOL . print_r( $result, true ) );
		return $result;
	}

	private function post( $URI ) {
		$url = "{$this->api_url}{$this->owner}/{$URI}";
		$args = array(
			'headers' => $this->getHeaders(),
		);
		$result = wp_remote_post( $url, $args );
		if ( CONDUCTRICS_DEBUG )
			file_put_contents( '/tmp/post.txt',  print_r( $args, true ) . PHP_EOL . $url . PHP_EOL . print_r( $result, true ) );
		return $result;
	}
	
}

$conductrics_plugin = new Conductrics();
add_action( 'init', array( $conductrics_plugin, 'onInit' ) );
