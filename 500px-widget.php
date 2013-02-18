<?php
/**
 * Plugin Name: 500px Widget
 * Plugin URI: http://romantelychko.com/downloads/wordpress/plugins/500px-widget.latest.zip
 * Description: Displays photos from 500px.com as widget with many options.
 * Version: 0.1
 * Author: Roman Telychko
 * Author URI: http://romantelychko.com
*/

///////////////////////////////////////////////////////////////////////////////

/**
 * 500px Widget.
 */
class Widget_500px extends WP_Widget 
{
    ///////////////////////////////////////////////////////////////////////////
        
    protected $defaults = array(
        'widget_id'                 => '500px_widget',

        'title'                     => '500px',
        'consumer_key'              => '5JwOJabC89Cb5uvgHmCJgYDAGXG9TwJ5fjOEg9Pk',
        'feature'                   => 1,
        'feature_username'          => '',
        'feature_tag'               => '',
        'sort_by'                   => 1,
        'count'                     => 6,
        'thumb_size'                => 1,
        'cache_lifetime'            => 3600,
        );

    ///////////////////////////////////////////////////////////////////////////

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() 
	{	
		parent::__construct(
	 		$this->defaults['widget_id'],
			'500px Widget',
			array(
			    'description'   => 'Displays photos from 500px.com', 
			    'classname'     => $this->defaults['widget_id'],
			    ),
		    array(
			    'width'     => 250,
			    'height'    => 350,
		    )
		);
	}
	
    ///////////////////////////////////////////////////////////////////////////

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param       array       $args               Widget arguments.
	 * @param       array       $instance           Saved values from database.
	 */
	public function widget( $args, $instance ) 
	{
	    // args
	    $args = array_merge( $this->defaults, $args );
		
        // cache key
        $cache_key = 'widget_500px_'.dechex(crc32( $args['widget_id'] ));

        // try to get cached data from transient cache
        $html = get_transient( $cache_key );

        if( empty($html) )
        {   
            $photos = $this->getPhotos( $instance );
            
		    if( empty($photos) )
		    {
		        return false;
		    }
		    
		    $title = apply_filters( 'widget_title', $instance['title'] );
     
            $html = $args['before_widget'];

		    if( !empty( $title ) )
		    {
			    $html .= $args['before_title'].$title.$args['after_title'];
		    }
		
		    $html .= $this->getHTML( $photos, $instance );

		    $html .= $args['after_widget'];
	
            // store result to cache
            set_transient( $cache_key, $html, $args['cache_lifetime'] );	            
		}

		echo( $html );		
	}
	
    ///////////////////////////////////////////////////////////////////////////
	
	/**
	 * Returns Photos
	 *
	 * @param       array       $args
	 * @return      array
	 */
	public function getPhotos( $args = array() )
	{
	    $url = 'https://api.500px.com/v1/photos';

	    switch( $args['sort_by'] )
	    {
	        case 1:             // Time of upload (Most recent first)
	        default:
        	    $url_sort = 'created_at';	            
	            break;
            
            case 2:             // Rating (Highest rated first)
        	    $url_sort = 'rating';	            
	            break;
         
            case 3:             // View count (Most viewed first)
        	    $url_sort = 'times_viewed';	            
	            break;
                    
            case 4:             // Votes count (Most voted first)
        	    $url_sort = 'votes_count';	            
	            break;
                                        
            case 5:             // Favorites count (Most favorited first)
        	    $url_sort = 'favorites_count';	            
	            break;
                    
            case 6:             // Comments count (Most commented first)
        	    $url_sort = 'comments_count';	            
	            break;
                                        
            case 7:             // Original date (Most recent first)
        	    $url_sort = 'taken_at';	            
	            break;
                                        
	    }
	    
	    #$url_params .= '?consumer_key='.$args['consumer_key'].'&sort='.$url_sort.'&rpp='.$args['count'].'&image_size='.$args['thumb_size'];
	    $url_params .= '?consumer_key='.$this->defaults['consumer_key'].'&sort='.$url_sort.'&rpp='.$args['count'].'&image_size='.$args['thumb_size'];	    

	    switch( $args['feature'] )
	    {
	        case 1:             // Popular Photos
	        default:
	            $url .= $url_params.'&feature=popular';
	            break;
	            
	        case 2:             // Upcoming Photos
	            $url .= $url_params.'&feature=upcoming';
	            break;
	            
	        case 3:             // Editors' Choice Photos
	            $url .= $url_params.'&feature=editors';
	            break;
	            
	        case 4:             // Fresh Today Photos
	            $url .= $url_params.'&feature=fresh_today';
	            break;
	            
	        case 5:             // Fresh Yesterday Photos
	            $url .= $url_params.'&feature=fresh_yesterday';
	            break;
	            
	        case 6:             // Fresh This Week Photos
	            $url .= $url_params.'&feature=fresh_week';
	            break;
	            
	        case 7:             // User Photos
	            $url .= $url_params.'&feature=user&username='.$args['feature_username'];
	            break;
	            
	        case 8:             // User Friends Photos
	            $url .= $url_params.'&feature=user_friends&username='.$args['feature_username'];
	            break;
	            
	        case 9:             // User Favorites Photos
	            $url .= $url_params.'&feature=user_favorites&username='.$args['feature_username'];
	            break;
	            
	        case 10:            // Tag Photos
	            $url .= '/search'.$url_params.'&tag='.$args['feature_tag'];
	            break;
	    }

    	$data = file_get_contents( $url );
	
        if( !empty($data) )
        {	
	        return json_decode( $data, true );
        }
        
        /*
            TODO: add CURL support if allow_url_fopen==off
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->page);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $data = curl_exec($ch);
            curl_close($ch);
            
        */

	    return array();
	}
	
    ///////////////////////////////////////////////////////////////////////////
	
	/**
	 * Returns HTML of photos
	 *
	 * @param       array       $photos
	 * @param       array       $args
	 * @return      string
	 */
	public function getHTML( $photos = array(), $args = array() )
	{	
	    if( empty($photos) || !isset($photos['photos']) || empty($photos['photos']) )
	    {
	        return false;
	    }
	    
	    // args
	    $args = array_merge( $this->defaults, $args );
	    
	    switch( $args['thumb_size'] )
	    {
	        case 1:
	        default:
	            $width  = $height = '70';
	            break;
	            
	        case 2:
	            $width  = $height = '140';
	            break;

	        case 3:
	            $width  = $height = '280';
	            break;
	            
	        case 4:
	            $width  = '';
	            $height = '';
	            break;
	    }

	    $html = '';
	    
	    foreach( $photos['photos'] as $photo )
	    {
	        $html .= 
	            '<a href="http://500px.com/photo/'.$photo['id'].'" target="_BLANK" rel="nofollow" title="'.$photo['name'].'">'.
	                '<img src="'.$photo['image_url'].'"'.( $width ? ' width="'.$width.'"' : '' ).( $height ? ' height="'.$height.'"' : '' ).' alt="'.$photo['name'].'" />'.
                '</a> ';
	    }
	    
	    return $html;	
	}
	
	///////////////////////////////////////////////////////////////////////////
	
	/**
	 * Clear transient widget cache
	 *
	 * @return      bool
	 */
	public function clearCache()
	{	
	    global $wpdb;
	
	    $q = '
	        SELECT
		        option_name     as name
	        FROM
		        '.$wpdb->options.'
	        WHERE	
	            option_name LIKE \'_transient_widget_500px_%\'';

	    $transients = $wpdb->get_results($q);
	    
	    if( !empty($transients) )
	    {
	        foreach( $transients as $transient )
	        {
	            delete_transient( str_replace( '_transient_', '', $transient->name ) );
	        }
	    }
	    
	    return true;
	}
	
    ///////////////////////////////////////////////////////////////////////////

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param       array       $new_instance       Values just sent to be saved.
	 * @param       array       $old_instance       Previously saved values from database.
	 *
	 * @return      array                           Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) 
	{
	    // drop cache
	    $this->clearCache();
	
	    // return sanitized data
		return 
		    array(
		        'title'                         => trim( strip_tags( $new_instance['title'] ) ),
		        #'consumer_key'                  => trim( preg_replace( '#[^0-9A-Za-z]#', '', strip_tags( $new_instance['consumer_key'] ) ) ),
                'feature'                       => intval( preg_replace( '#[^0-9]#', '', $new_instance['feature'] ) ),
                'feature_username'              => trim( strip_tags( $new_instance['feature_username'] ) ),
                'feature_tag'                   => trim( strip_tags( $new_instance['feature_tag'] ) ),
                'sort_by'                       => intval( preg_replace( '#[^0-9]#', '', $new_instance['sort_by'] ) ),
		        'count'                         => intval( preg_replace( '#[^0-9]#', '', $new_instance['count'] ) ),
		        'thumb_size'                    => intval( preg_replace( '#[^0-9]#', '', $new_instance['thumb_size'] ) ),
		        'cache_lifetime'                => intval( preg_replace( '#[^0-9]#', '', $new_instance['cache_lifetime'] ) ),
		    );
	}
	
    ///////////////////////////////////////////////////////////////////////////

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param       array       $instance           Previously saved values from database.
	 */
	public function form( $instance ) 
	{   
	    // defaults
	    $title                      = $this->defaults['title'];
	    #$consumer_key               = $this->defaults['consumer_key'];	    
	    $feature                    = $this->defaults['feature'];
	    $feature_username           = $this->defaults['feature_username'];
	    $feature_tag                = $this->defaults['feature_tag'];
	    $sort_by                    = $this->defaults['sort_by'];
	    $count                      = $this->defaults['count'];
	    $thumb_size                 = $this->defaults['thumb_size'];
	    $cache_lifetime             = $this->defaults['cache_lifetime'];

        // set values
		if( isset($instance['title']) && strlen($instance['title'])>1 ) 
		{
			$title = $instance['title'];
		}
		
    	#if( isset($instance['consumer_key']) && strlen($instance['consumer_key'])>1 ) 
		#{
			#$consumer_key = $instance['consumer_key'];
		#}
		
		if( isset($instance['feature']) && intval($instance['feature'])>0 ) 
		{
			$feature = intval($instance['feature']);
		}
		
		if( isset($instance['feature_username']) && strlen($instance['feature_username'])>1 ) 
		{
			$feature_username = $instance['feature_username'];
		}
		
		if( isset($instance['feature_tag']) && strlen($instance['feature_tag'])>1 ) 
		{
			$feature_tag = $instance['feature_tag'];
		}
		
		if( isset($instance['sort_by']) && intval($instance['sort_by'])>0 ) 
		{
			$sort_by = intval($instance['sort_by']);
		}
		
		if( isset($instance['count']) && intval($instance['count'])>0 ) 
		{
			$count = intval($instance['count']);
		}
		
		if( isset($instance['thumb_size']) && intval($instance['thumb_size'])>0 ) 
		{
			$thumb_size = intval($instance['thumb_size']);
		}
		
		if( isset($instance['cache_lifetime']) && intval($instance['cache_lifetime'])>0 ) 
		{
			$cache_lifetime = intval($instance['cache_lifetime']);
		}
	
	    // html	
		echo(
		    '<script type="text/javascript">
		        function Widget_500px_FeatureChange( value )
		        {
                    if( value<7 )
                    {
                        document.getElementById(\''.$this->get_field_id('p_feature_username').'\').style.display = "none";
                        document.getElementById(\''.$this->get_field_id('p_feature_tag').'\').style.display = "none";
                    }
                    else if( value==10 )
                    {
                        document.getElementById(\''.$this->get_field_id('p_feature_username').'\').style.display = "none";
                        document.getElementById(\''.$this->get_field_id('p_feature_tag').'\').style.display = "block";                    
                    }
                    else
                    {
                        document.getElementById(\''.$this->get_field_id('p_feature_username').'\').style.display = "block";
                        document.getElementById(\''.$this->get_field_id('p_feature_tag').'\').style.display = "none";
                    }                                       		        
                    
                    return true;
		        }'.		    
		    '</script>'.
		
		    '<p>'.
		        '<label for="'.$this->get_field_id('title').'">Widget title:</label>'.
		        '<input class="widefat" id="'.$this->get_field_id('title').'" name="'.$this->get_field_name('title').'" type="text" value="'.esc_attr($title).'" />'.
		    '</p>'.
		    #'<p>'.
		        #'<label for="'.$this->get_field_id('consumer_key').'">Consumer key (from <a href="http://500px.com/settings/applications" target="_BLANK">500px applications</a>):</label>'.
		        #'<input class="widefat" id="'.$this->get_field_id('consumer_key').'" name="'.$this->get_field_name('consumer_key').'" type="text" value="'.esc_attr($consumer_key).'" placeholder="&lt;register your 500px-application&gt;" />'.
		    #'</p>'.
		    '<p>'.
		        '<label for="'.$this->get_field_id('feature').'">What to display:</label>'.
		        '<select class="widefat" id="'.$this->get_field_id('feature').'" name="'.$this->get_field_name('feature').'" onchange="return Widget_500px_FeatureChange( this.value );">'.		            
		            '<option value="1"'.( $feature<2 || $feature>10 ? ' selected="selected"' : '' ).'>Popular Photos</option>'.
		            '<option value="2"'.( $feature==2 ? ' selected="selected"' : '' ).'>Upcoming Photos</option>'.
		            '<option value="3"'.( $feature==3 ? ' selected="selected"' : '' ).'>Editors\' Choice Photos</option>'.
		            '<option value="4"'.( $feature==4 ? ' selected="selected"' : '' ).'>Fresh Today Photos</option>'.
		            '<option value="5"'.( $feature==5 ? ' selected="selected"' : '' ).'>Fresh Yesterday Photos</option>'.
		            '<option value="6"'.( $feature==6 ? ' selected="selected"' : '' ).'>Fresh This Week Photos</option>'.
		            
		            '<option value="7"'.( $feature==7 ? ' selected="selected"' : '' ).'>User Photos</option>'.
		            '<option value="8"'.( $feature==8 ? ' selected="selected"' : '' ).'>User Friends Photos</option>'.
		            '<option value="9"'.( $feature==9 ? ' selected="selected"' : '' ).'>User Favorites Photos</option>'.
		            
		            '<option value="10"'.( $feature==10 ? ' selected="selected"' : '' ).'>Tag Photos</option>'.
		        '</select>'.
		    '</p>'.
		    '<p id="'.$this->get_field_id('p_feature_username').'"'.( $feature>6 && $feature<10 ? ' style="display:block;"' : ' style="display:none;"' ).'>'.
		        '<label for="'.$this->get_field_id('feature_username').'">Username:</label>'.
		        '<input class="widefat" id="'.$this->get_field_id('feature_username').'" name="'.$this->get_field_name('feature_username').'" type="text" value="'.esc_attr($feature_username).'" />'.
		    '</p>'.
		    '<p id="'.$this->get_field_id('p_feature_tag').'"'.( $feature==10 ? ' style="display:block;"' : ' style="display:none;"' ).'>'.
		        '<label for="'.$this->get_field_id('feature_tag').'">Tag:</label>'.
		        '<input class="widefat" id="'.$this->get_field_id('feature_tag').'" name="'.$this->get_field_name('feature_tag').'" type="text" value="'.esc_attr($feature_tag).'" />'.
		    '</p>'.
		    '<p>'.
		        '<label for="'.$this->get_field_id('sort_by').'">Sort by:</label>'.
		        '<select class="widefat" id="'.$this->get_field_id('sort_by').'" name="'.$this->get_field_name('sort_by').'">'.		            
		            '<option value="1"'.( $sort_by<2 || $sort_by>7 ? ' selected="selected"' : '' ).'>Time of upload (Most recent first)</option>'.
		            '<option value="2"'.( $sort_by==2 ? ' selected="selected"' : '' ).'>Rating (Highest rated first)</option>'.
		            '<option value="3"'.( $sort_by==3 ? ' selected="selected"' : '' ).'>View count (Most viewed first)</option>'.
		            '<option value="4"'.( $sort_by==4 ? ' selected="selected"' : '' ).'>Votes count (Most voted first)</option>'.
		            '<option value="5"'.( $sort_by==5 ? ' selected="selected"' : '' ).'>Favorites count (Most favorited first)</option>'.
		            '<option value="6"'.( $sort_by==6 ? ' selected="selected"' : '' ).'>Comments count (Most commented first)</option>'.
		            '<option value="7"'.( $sort_by==7 ? ' selected="selected"' : '' ).'>Original date (Most recent first)</option>'.
		        '</select>'.
		    '</p>'.	
		    '<p>'.
		        '<label for="'.$this->get_field_id('count').'">Display count:</label>'.
		        '<input class="widefat" id="'.$this->get_field_id('count').'" name="'.$this->get_field_name('count').'" type="text" value="'.esc_attr($count).'" />'.
		    '</p>'.
		    '<p>'.
		        '<label for="'.$this->get_field_id('thumb_size').'">Thumb size (px):</label>'.
		        '<select class="widefat" id="'.$this->get_field_id('thumb_size').'" name="'.$this->get_field_name('thumb_size').'">'.		            
		            '<option value="1"'.( $thumb_size<2 || $thumb_size>4 ? ' selected="selected"' : '' ).'>70x70</option>'.
		            '<option value="2"'.( $thumb_size==2 ? ' selected="selected"' : '' ).'>140x140</option>'.
		            '<option value="3"'.( $thumb_size==3 ? ' selected="selected"' : '' ).'>280x280</option>'.
		            '<option value="4"'.( $thumb_size==4 ? ' selected="selected"' : '' ).'>900x / x900</option>'.
		        '</select>'.
		    '</p>'.		
		    '<p>'.
		        '<label for="'.$this->get_field_id('cache_lifetime').'">Cache lifetime (sec):</label>'.
		        '<input class="widefat" id="'.$this->get_field_id('cache_lifetime').'" name="'.$this->get_field_name('cache_lifetime').'" type="text" value="'.esc_attr($cache_lifetime).'" />'.
		    '</p>'.
		    '<p>'.
		        'I forgot something? <a href="http://wordpress.org/support/plugin/500px-widget" target="_BLANK">You can write to me!</a>'.
		    '</p>'
		    );
	}

    ///////////////////////////////////////////////////////////////////////////
}

///////////////////////////////////////////////////////////////////////////////

// register AJAX Hits Counter: Popular Posts Widget
add_action( 'widgets_init', create_function( '', 'register_widget( "Widget_500px" );' ) );

///////////////////////////////////////////////////////////////////////////////

