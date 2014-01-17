<?php
/*
Plugin Name: Transients Demo Plugin
Plugin URI: http://logoscreative.co/wcphx14
Description: Demo plugin for examples given in "Temporary Cache Assistance (Transients API)"
Version: 1.0
Author: Cliff Seal
Author URI: http://logoscreative.co
Author Email: cliff@logoscreative.co
*/

if ( ! defined( 'WPINC' ) ) {
    die;
}

class Transient_Shortcodes {

    function __construct() {

        add_shortcode( 'tagcloud', array( $this, 'get_tag_cloud' ) );
	    add_action( 'edit_post',  array( $this, 'edit_term_delete_tag_cloud' ) );
	    add_shortcode( 'issues', array( $this, 'we_have_issues' ) );
	    add_action( 'init', array( $this, 'refresh_my_issues' ) );
	    add_action( 'wp_before_admin_bar_render', array( $this, 'refresh_via_admin_bar' ) );
	    add_shortcode( 'topcommenters', array( $this, 'query_for_commenters' ) );
	    add_action( 'comment_post', array( $this, 'delete_query_for_commenters' ) );
	    add_shortcode( 'popularposts', array( $this, 'popular_posts' ) );
	    add_action( 'edit_post',  array( $this, 'clear_popular_posts' ) );
	    add_shortcode( 'popularpostspanic', array( $this, 'popular_posts_panic' ) );
	    add_action( 'wp', array( $this, 'schedule_renew_popular_posts' ) );
	    add_action( 'hourly_renew_popular_posts', array( $this, 'renew_popular_posts' ) );

    }



	/**
	 * Cache and Return Tag Cloud for 24 Hours
	 * @return string
	 */

    public function get_tag_cloud() {

	    if ( false === ( $tag_cloud = get_transient( 'my_tag_cloud' ) ) ) {

		    $tag_cloud = wp_tag_cloud( 'echo=0' );
		    set_transient( 'my_tag_cloud', $tag_cloud, 60*60*24 );

		    // Show where things are coming from
		    $fromcache = false;

	    } else {

		    $tag_cloud = get_transient( 'my_tag_cloud' );

		    // Show where things are coming from
		    $fromcache = true;

	    }

	    // Show where things are coming from
	    if ( ( isset($_GET['debug']) && 'true' === $_GET['debug'] ) && true === $fromcache ) {

		    $tag_cloud .= '<p>From Cache.</p>';

	    } elseif ( isset($_GET['debug']) && 'true' === $_GET['debug'] ) {

		    $tag_cloud .= '<p>Not from Cache.</p>';

	    }

	    return $tag_cloud;

    }

	/**
	 * Delete Tag Cloud Cache When Post is Edited
	 */

	public function edit_term_delete_tag_cloud() {

		delete_transient( 'my_tag_cloud' );

	}

	/**
	 * Cache and Return 5 GitHub Issues for 24 Hours
	 * @return string
	 */

	public function we_have_issues() {

		if ( false === ( $issues = get_transient( 'we_have_issues' ) ) ) {

			$response = wp_remote_get('https://api.github.com/repos/twbs/bootstrap/issues?assignee');

			if ( is_wp_error( $response ) ) {

				$error_message = $response->get_error_message();
				echo "This borked: " . $error_message;

			} else {

				$issues = wp_remote_retrieve_body($response);
				set_transient( 'we_have_issues', $issues, 60*60*24 );
			}

			// Show where things are coming from
			$fromcache = false;

		} else {

			$issues = get_transient( 'we_have_issues' );

			// Show where things are coming from
			$fromcache = true;

		}

		$issues = json_decode($issues, true);
		$issuereturn = '';

		for ( $i = 0; $i < 5; $i++ ) {

			$issuereturn .= "<h3><a href='" . $issues[$i]["html_url"] . "'>". $issues[$i]["title"] . "</a></h3>";

		}

		// Show where things are coming from
		if ( ( isset($_GET['debug']) && 'true' === $_GET['debug'] ) && true === $fromcache ) {

			$issuereturn .= '<p>From Cache.</p>';

		} elseif ( isset($_GET['debug']) && 'true' === $_GET['debug'] ) {

			$issuereturn .= '<p>Not from Cache.</p>';

		}

		return $issuereturn;
	}

	/**
	 * Delete GitHub Issues Cache
	 */

	public function refresh_my_issues() {

		if ( current_user_can('edit_plugins') && isset($_GET['forcedel']) && $_GET['forcedel'] === 'yes' ) {

			delete_transient( 'we_have_issues' );

		}

	}

	/**
	 * Add Refresh Link to Admin Bar
	 */

	public function refresh_via_admin_bar() {

		global $wp_admin_bar;

		$wp_admin_bar->add_menu( array(
			'title' => __('Refresh'),
			'href' => '?forcedel=yes',
			'id' => 'refresh-issues',
			'parent' => false
		) );

	}

	/**
	 * Cache and Return 10 Top Commenters
	 * @return string
	 */

	public function query_for_commenters() {

		if ( false === ( $commenters = get_transient( 'top_commenters_cached' ) ) ) {

			global $wpdb;
			$commenters = $wpdb->get_results("select count(comment_author) as comments_count, comment_author, comment_type from $wpdb->comments where comment_type != 'pingback' and comment_author != '' and comment_approved = '1' group by comment_author order by comment_author desc LIMIT 10");
			set_transient( 'top_commenters_cached', $commenters, 60*60*24 );

			// Show where things are coming from
			$fromcache = false;

		} else {

			$commenters = get_transient( 'top_commenters_cached' );

			// Show where things are coming from
			$fromcache = true;

		}

		$comment_list = '<ol>';

		foreach($commenters as $commenter) {

			$comment_list .= '<li>';
			$comment_list .= $commenter->comment_author;
			$comment_list .= ' (' . $commenter->comments_count . ')';
			$comment_list .= '</li>';

		}

		$comment_list .= '</ol>';

		// Show where things are coming from
		if ( ( isset($_GET['debug']) && 'true' === $_GET['debug'] ) && true === $fromcache ) {

			$comment_list .= '<p>From Cache.</p>';

		} elseif ( isset($_GET['debug']) && 'true' === $_GET['debug'] ) {

			$comment_list .= '<p>Not from Cache.</p>';

		}

		return $comment_list;

	}

	/**
	 * Delete Top Commenters Cache
	 */

	public function delete_query_for_commenters() {

		delete_transient( 'top_commenters_cached' );

	}

	/**
	 * Cache and Return Popular Posts for 24 Hours
	 * @param string $atts
	 * @return string
	 */

	public function popular_posts( $atts ) {

		extract(
			shortcode_atts(
				array(
					'num' => 10
				),
				$atts
			)
		);

		if ( false === ( $popular = get_transient( 'popular_posts' . $num ) ) ) {

			$popular = new WP_Query( array(
				'orderby' => 'comment_count',
				'posts_per_page' => $num
			) );

			set_transient( 'popular_posts' . $num, $popular, 60*60*24 );

			// Show where things are coming from
			$fromcache = false;

		} else {

			$popular = get_transient( 'popular_posts' . $num );

			// Show where things are coming from
			$fromcache = true;

		}

		$output = '';

		if ( $popular->have_posts() ) {

			while ( $popular->have_posts() ) {

				$popular->the_post();

				$output .= '<h4>' . get_the_title() . '</h4>' . get_the_excerpt();

			}

			wp_reset_postdata();

		}

		// Show where things are coming from
		if ( ( isset($_GET['debug']) && 'true' === $_GET['debug'] ) && true === $fromcache ) {

			$output .= '<p>From Cache.</p>';

		} elseif ( isset($_GET['debug']) && 'true' === $_GET['debug'] ) {

			$output .= '<p>Not from Cache.</p>';

		}

		return $output;

	}

	/**
	 * Delete Popular Posts Cache
	 */

	public function clear_popular_posts() {

		for ( $i = 0; $i < 50; $i++ ) {

			delete_transient( 'popular_posts' . $i );

		}

	}

	/**
	 * Cache and Return Popular Posts for 24 Hours
	 * @param string $atts
	 * @return string
	 */

	public function popular_posts_panic( $atts ) {

		extract(
			shortcode_atts(
				array(
					'num' => 10
				),
				$atts
			)
		);

		if ( false === ( $popular = get_transient( 'popular_posts' . $num ) ) ) {

			$popular = '';

			// Show where things are coming from
			$fromcache = false;

		} else {

			$popular = get_transient( 'popular_posts' . $num );

			// Show where things are coming from
			$fromcache = true;

		}

		$output = '';

		if ( '' !== $popular ) {

			if ( $popular->have_posts() ) {

				while ( $popular->have_posts() ) {

					$popular->the_post();

					$output .= '<h4>' . get_the_title() . '</h4>' . get_the_excerpt();

				}

				wp_reset_postdata();

			}

		}

		// Show where things are coming from
		if ( ( isset($_GET['debug']) && 'true' === $_GET['debug'] ) && true === $fromcache ) {

			$output .= '<p>From Cache.</p>';

		} elseif ( isset($_GET['debug']) && 'true' === $_GET['debug'] ) {

			$output .= '<p>Not from Cache.</p>';

		}

		return $output;

	}

	/**
	 * Update Popular Posts Cache
	 */

	public function renew_popular_posts() {

		for ( $i = 0; $i < 50; $i++ ) {

			$query = new WP_Query( array(
				'orderby' => 'comment_count',
				'posts_per_page' => $i
			) );

			set_transient( 'popular_posts' . $i, $query, 60*60*24*365 );

		}
	}

	/**
	 * Schedule Cache Updating
	 */

	public function schedule_renew_popular_posts() {
		if ( !wp_next_scheduled( 'hourly_renew_popular_posts' ) ) {
			wp_schedule_event( time(), 'hourly', 'hourly_renew_popular_posts' );
		}
	}

}


$transientshortcodes = New Transient_Shortcodes();
