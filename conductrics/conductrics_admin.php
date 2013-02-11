<?php

class Conductrics_Admin {

	private $exclude_page_ids = NULL;
	private $self_label_new = NULL;

	public function __construct() {
		add_action( 'current_screen', array( $this, 'onCurrentScreen' ) );
		add_action( 'submitpage_box', array( $this, 'onSubmitPostBox') );
		add_filter( 'wp_insert_post_data' , array( $this, 'filterInsertPost' ), 10, 2 );

		//admin menu
		add_action( 'admin_init', array( $this, 'onAdminInit' ) );
		add_action( 'admin_menu', array( $this, 'onAdminMenu' ) );
	}

	public function onAdminMenu() {
		add_options_page( __( 'Conductrics Settings', 'conductrics' ),
						  __( 'Conductrics', 'conductrics' ),
						  'publish_pages',
						  'conductrics',
						  array( $this, 'printConductricsSettings' ) );
	}

	public function onAdminInit() {
		register_setting( 'conductrics_options', 'conductrics_apikey', array( $this, 'sanitizeAPIKey' ) );
		register_setting( 'conductrics_options', 'conductrics_owner', array( $this, 'sanitizeOwnerKey' ) );
		add_settings_section( 'conductrics_api', 'Conductrics Settings', array( $this, 'printAPISettingsLabel' ), 'conductrics' );
		add_settings_field( 'conductrics_apikey', 'Conductrics API Key', array( $this, 'printAPIKeyInput' ), 'conductrics', 'conductrics_api' );
		add_settings_field( 'conductrics_owner', 'Conductrics Owner Key', array( $this, 'printOwnerKeyInput' ), 'conductrics', 'conductrics_api' );
	}

	public function printConductricsSettings() {
		?>
		<div class="wrap">
		<div class="icon32" id="icon-options-general"><br></div>
			<h2><?php _e( 'Conductrics Settings', 'conductrics' ) ?></h2>

			<form action="options.php" method="post">
			 	<?php settings_fields( 'conductrics_options' ); ?>
				<?php do_settings_sections( 'conductrics' ); ?> 
				<p class="submit"><input type="submit" value="Save Changes" class="button button-primary" id="submit" name="submit"></p>
			</form>
		</div>
		<?php
	}

	public function printAPISettingsLabel() {
		echo '<p>API Settings</p>';
	}

	public function printAPIKeyInput() {
		$apikey = get_option( 'conductrics_apikey' );
		echo "<input id='conductrics_apikey' name='conductrics_apikey' size='40' type='text' value='{$apikey}' />";
	}

	public function sanitizeAPIKey( $key ) {
		return $key;
	}
	
	public function printOwnerKeyInput() {
		$owner = get_option( 'conductrics_owner' );
		echo "<input id='conductrics_owner' name='conductrics_owner' size='40' type='text' value='{$owner}' />";
	}

	public function sanitizeOwnerKey( $key ) {
		return $key;
	}
	
	public function onCurrentScreen( $current_screen ) {
		if ( $current_screen->id == 'page' ) {
			wp_register_script( 'conductrics-page-box', CONDUCTRICS_PLUGIN_URL . 'submit_page_box.js', array( 'jquery-ui-core', 'jquery-ui-spinner' ) );
			wp_enqueue_script( 'conductrics-page-box' );

			wp_register_style( 'conductrics-jquery-theme', CONDUCTRICS_PLUGIN_URL . 'jquery-theme/base/jquery-ui.css' );	
			wp_enqueue_style( 'conductrics-jquery-theme' );

			wp_register_style( 'conductrics-admin-style', CONDUCTRICS_PLUGIN_URL . 'conductrics_admin.css' );	
			wp_enqueue_style( 'conductrics-admin-style' );
		}
	}
	
	/**
	 * Shows a box like a metabox, but on the right column above the publish box
	 */
	public function onSubmitPostBox() {
		//determine if this is a new post - there may be a better way to do this
		$id = isset( $_GET['post'] ) ? $_GET['post'] : NULL;
		$post = get_post( $id );
		$ptype_obj = get_post_type_object( $post->post_type );
		$agent = $this->getPostAgent( $id );
		$checked = ! empty( $agent['type'] );
		$immutable = $post->post_status == 'publish' && $checked ? true : false;
		$this->self_label_new = sprintf( __( 'This %s', 'conductrics' ), $ptype_obj->labels->singular_name );
		?>
		<div id="conductrics-postbox-div" class="postbox">
			<div class="handlediv" title="<?php _e( 'Click to toggle' ) ?>"></div>
			<h3 class="hndle"><span><?php _e( 'Conductrics Agent', 'conductrics' ) ?></span></h3>
			<div class="inside">
			<? if ( ! ( $apikey = get_option( 'conductrics_apikey' ) ) || ! ( $owner = get_option( 'conductrics_owner' ) ) ): ?>
				<p><?php echo sprintf( __( 'Enable Conductrics by entering your <a href="%s">Conductrics API &amp; Owner Keys</a>' , 'conductrics' ), admin_url( 'options-general.php?page=conductrics' ) ) ?></p>
			<?php elseif ( $id && ( $parent_id = get_post_meta( $id, 'conductrics_test_parent', true ) ) && $id != $parent_id ): ?>
			 	<p><?php echo sprintf( __( 'This %s is part of an <a href="%s">existing Conductrics Test</a>', 'conductrics' ), $ptype_obj->labels->singular_name, admin_url( "post.php?post={$parent_id}&action=edit" ) ) ?></p>
			<?php else: ?>
			 
				<div class="conductrics-is-test">
					<label for="conductrics_test"><?php echo sprintf( __( 'Test this %s:', 'conductrics' ), $ptype_obj->labels->singular_name ) ?><label>
					<input type="checkbox" id="conductrics_test" name="conductrics_test" disabled="disabled" value="1"<?php echo $checked ?  ' checked="checked"' : '' ?>/>
				</div>

				<div id="conductrics_test_params" <?php echo $checked ? '' : 'style="display: none;"' ?>>
					<?php if ( $immutable ): ?>
						<p><?php _e( 'This test has been published, to make any changes, you need to create a new test', 'conductrics' ) ?></p>
					<?php endif; ?>
					<p><strong><?php _e( 'Options', 'conductrics' ) ?></strong></p>
					<div id="conductrics_option">
					<?php
					if ( $immutable ):
						?><input type="hidden" name="conductrics_immutable" value="true" /><?php
						foreach ( $agent['options'] as $option_id => $unused ):
							$option = get_post( $option_id );
							?><p><?php echo sprintf( __( '%s <a href="%s">View</a> <a href="%s">Edit</a>', 'conductrics' ), $option->post_title, $option->guid, admin_url( "post.php?post={$option->ID}&action=edit" ) ) ?></p><?php
						endforeach;
					else:
						if ( ( $option_count = count( $agent['options'] ) ) < 2 ) {
							for ( $i = $option_count; $i < 2; $i ++ ) {
								$agent['options'][] = false;
							}
						}
						$index = 0;
						$first_option = true;
						foreach ( $agent['options'] as $option_id => $value ) :
							$selected_page = $option_id;
							if ( $first_option ) {
								$first_option = false;
								if ( ! $value ) {
									if ( $id )
										$selected_page = $id;
									else
										$selected_page = $this->self_label_new;
								}
							}
							?>
							<div>
								<?php echo $this->getPagesDropdown( "conductrics_option_id_{$index}", $id, $selected_page ) ?>
								<?php if ( $index > 1 ): ?>
									<span class="conductrics-delete">&nbsp;<a class="dropdown-check">X</a>
								<?php endif; ?>
							</div>
						<?php $index++; endforeach; ?>
						<div id="add_option_container"><a id="conductrics_add_option"><? _e( 'Add Another Option', 'conductrics' ) ?></a></div>
					<?php endif; //immutable options ?>
					</div>

					<p><strong><?php _e( 'Goals', 'conductrics' ) ?></strong>&nbsp<span><?php _e( '(Page Name / Reward #)', 'conductrics' ) ?></span></p>
					<div id="conductrics_goal">
					<?php
					if ( $immutable ):
						foreach ( $agent['goals'] as $goal_id => $value ):
							$goal = get_post( $goal_id );
							?><p><?php echo sprintf( __( '%s <a href="%s">View</a> <a href="%s">Edit</a>', 'conductrics' ), $goal->post_title, $goal->guid, admin_url( "post.php?post={$goal->ID}&action=edit" ) ) ?>&nbsp;<a title="Reward"><?php echo $value ?></a></p><?php
						endforeach;
					else:
						if ( empty( $agent['goals'] ) ) {
							$agent['goals'][] = 1;
						}
						$index = 0;
						foreach ( $agent['goals'] as $goal_id => $value ) :
						?>
							<div>
								<?php echo $this->getPagesDropdown( "conductrics_goal_id_{$index}", $id, $goal_id ) ?>
								<input class="conductrics-goal-spinner" name="conductrics_goal_value_<?php echo $index; ?>" value="<?php echo $value ?>" />
								<?php if ( $index > 0 ): ?>
									<span class="conductrics-delete">&nbsp;<a class="dropdown-check">X</a>
								<?php endif; ?>
							</div>
						<?php $index++; endforeach; ?>
						<div id="add_goal_container"><a id="conductrics_add_goal"><? _e( 'Add Another Goal', 'conductrics' ) ?></a></div>
					<?php endif; //immutable goals ?>
					</div>

					<?php if ( $immutable ): ?>
						<p><?php echo sprintf( __( 'View stats for this test at %s', 'conductrics' ), '<a href="http://console.conductrics.com">console.conductrics.com</a>' ) ?></p>
					<?php endif; ?>
				</div>

				<div class="conductrics-option-boilerplate" style="display: none;">
					<?php echo $this->getPagesDropdown( 'pages_option_boilerplate', $id ) ?><span class="conductrics-delete">&nbsp;<a class="dropdown-check">X</a>
				</div>
				<div class="conductrics-goal-boilerplate" style="display: none;">
					<?php echo $this->getPagesDropdown( 'pages_goal_boilerplate', $id ) ?>
					<input class="conductrics-new-spinner" value="1" />
					<span class="conductrics-delete">&nbsp;<a class="dropdown-check">X</a>
				</div>
			<?php endif; ?>	
			</div>
		</div>
		<?php
	}

	private function getConductricsTestPageIDs( $parent_id = NULL) {
		if ( is_array( $this->exclude_page_ids ) )
			return $this->exclude_page_ids;

		$this->exclude_page_ids = array();

		//exclude test parents that aren't in this test
		$args = array( 'meta_key' => 'conductrics_test',
					   'post_status' => array( 'draft', 'publish' ),
		);		
		$pages = get_pages( $args );

		foreach ( $pages as $page ) {
			if ( $page->post_id != $parent_id )
				$this->exclude_page_ids[] = $page->ID;
		}

		/*
		//exclude test goals that aren't in this test
		$args = array(
			'meta_query' => array(
				array( 'key' => 'conductrics_goal' ),
				array( 'key' => 'conductrics_test_parent',
					   'value' => $parent_id,
					   'compare' => '!='
					   ),
			 ),
			'post_status' => array( 'draft', 'publish' ),
			'post_type' => 'page',
		);
		$query = new WP_Query( $args );
		$pages = $query->get_posts();

		foreach ( $pages as $page ) {
			$this->exclude_page_ids[] = $page->ID;
		}
		*/

		return $this->exclude_page_ids;
	}
	
	private function getPagesDropdown( $name, $parent_id = NULL, $selected = NULL ) {
		$exclude = $this->getConductricsTestPageIDs( $parent_id );

		$dropdown_args = array(
			'post_type'        => 'page',
			'name'             => $name,
			'sort_column'      => 'menu_order, post_title',
			'post_status'	   => array( 'publish', 'draft' ),
			'echo'             => 0,
	   	);

		if ( ! empty( $exclude ) )
			$dropdown_args['exclude'] = implode( ',', $exclude );
				
		if ( is_numeric( $selected ) )
			$dropdown_args['selected'] = $selected;
		else if ( $selected == $this->self_label_new ) {
			$dropdown_args['show_option_none'] = $selected;
		}

		return wp_dropdown_pages( $dropdown_args );
	}
	
	public function filterInsertPost( $data, $postarr ) {
		//skip quick edit
		if ( ! empty( $_REQUEST['_inline_edit'] ) )
			return $data;

		//don't do anything unless 'publish' or 'save draft' is chosen
		if ( ! in_array( $data['post_status'], array( 'publish', 'draft' ) ) )
			 return $data;

		//only save on pages
		if ( $data['post_type'] != 'page' )
			return $data;

		$this->savePostAgent( $postarr );

		return $data;
	}

	private function savePostAgent( $postarr ) {
		//old agent
		$oldagent = $this->getPostAgent( $postarr['ID'] );
		
		//build new agent from post array
		$newagent = array(
			'type' => NULL,
			'options' => array(),
			'goals' => array() );

		$old_options = array_keys( $oldagent['options'] );
		$old_goals = array_keys( $oldagent['goals'] );

		//don't update once the settings are immutable
		if ( isset( $postarr['conductrics_test'] ) && ! isset( $postarr['conductrics_immutable'] ) ) {
			$newagent['type'] = $postarr['post_type'];
			
			foreach ( $postarr as $key => $value ) {
				if ( strpos( $key, 'conductrics_option_id_' ) === 0 ) {
					if ( $value )
						$newagent['options'][$value] = true;
					else
						$newagent['options'][$postarr['ID']] = true; //for new pages where 'This Page' was selected
				} else if ( strpos( $key, 'conductrics_goal_id_' ) === 0 ) {
					$goal_key = 'conductrics_goal_value_' . substr( $key, 20 );
					$newagent['goals'][$value] = isset( $postarr[$goal_key] ) ? $postarr[$goal_key] : 1;
				}
			}

			$new_options = array_keys( $newagent['options'] );
			
			$removed_options = array_diff( $old_options, $new_options );
			$added_options = array_diff( $new_options, $old_options );
			$this->removeTestPostMeta( $removed_options );
			$this->addTestPostMeta( $added_options, $postarr['ID'] );

			//get the goal keys (post_ID) for this comparison
			$new_goals = array_keys( $newagent['goals'] );
			
			$removed_goals = array_diff( $old_goals, $new_goals );
			$added_goals = array_diff( $new_goals, $old_goals );
			$this->removeTestPostMeta( $removed_goals );
			$this->addTestPostMeta( $added_goals, $postarr['ID'] );
			//breadcrumb for goal
			$this->removeGoalPostMeta( $removed_goals );
			$this->addGoalPostMeta( $newagent['goals'] );

			update_post_meta( $postarr['ID'], 'conductrics_test', $newagent );
		} elseif ( ! isset( $postarr['conductrics_test'] ) ) {			
			$this->removeTestPostMeta( $old_options );
			$this->removeTestPostMeta( $old_goals );			
			$this->removeGoalPostMeta( $old_goals );			
			delete_post_meta( $postarr['ID'], 'conductrics_test' );
		}
	}

	private function addTestPostMeta( $array_ids, $parent_id ) {
		foreach ( $array_ids as $id ) {
			update_post_meta( $id, 'conductrics_test_parent', $parent_id );
		}
	}

	private function removeTestPostMeta( $array_ids ) {
		foreach ( $array_ids as $id ) {
			delete_post_meta( $id, 'conductrics_test_parent' );
		}
	}
	
	private function addGoalPostMeta( $array_ids ) {
		foreach ( $array_ids as $id => $value ) {
			update_post_meta( $id, 'conductrics_goal', $value );
		}
	}
	
	private function removeGoalPostMeta( $array_ids ) {
		foreach ( $array_ids as $id ) {
			delete_post_meta( $id, 'conductrics_goal' );
		}
	}

	private function getPostAgent( $id = NULL ) {
		$empty_agent = array(
			'type' => NULL,
			'options' => array(),
			'goals' => array() );

		if ( ! $id )
			return $empty_agent;
		
		$agent = get_post_meta( $id, 'conductrics_test', true );
		if ( ! is_array( $agent ) )
			return $empty_agent;

		//possibly convert old style agent
		return $agent;
	}
}