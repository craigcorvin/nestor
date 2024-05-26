<?php

/**
 * This is a smaple component to demostrate Nestor basics in a less than concise but easily understandable way.
 */
class Sample extends Component {

	/**
	 * component_info is a required method that each component must have. 
	 * this method returns an array of essential component information and configuration values.
	 * 
	 */
	public function component_info() {
	
		// the array which is returned when sample->component_info() method is called, and is used to store essential component information and configuration values.
		return array(
			
			// The first three key=>value pairs are used within Manage Components to provide display information about this component
			'name' => 'Sample', // A readable name for this component
			'description' => 'A sample nestor component', // A readable description about what this component does
			// Addtional information can be added to the description as well, such as 'description' => 'PHP version: ' . phpversion();
			'category' => 'sample component', // this value is used to group components with similar funcationalty.
			//
			// permissions allow for role specific permissions assignment withing Manage Componnets
			'permissions' => array(
				// each permission is described within an array
				// permissions are added to the user object by user role when the applicaiton launches
				// permissions can be checked by calling to the public function check_permissions('*permission_name*') method within class.components.php
				array(
					// the name of the permission
					'name' => 'sample_permission_one',
					// a readable description of the permission
					'description' => 'Seleted user roles can do what is allowed by permission one'
				),
				// and another
				array(
					'name' => 'sample_permission_two',
					'description' => 'Selected user roles can do what is allowed by permission two'
				)
			),
			//
			// recipe_fields provides configuration options for this component within recipes and are accessible in Manage Recipes
			// this field corresponds to public function recipe_fields($recipe) method in class.components.php
			// the method can be used instead of adding this field to component_info() if something very complicated is needed
			//
			// 'recipe_fields' => false
			// will prevent a component from being available within Manage Recipes and is used for components that modify behavior through hooks for example.
			//
			'recipe_fields' => array(
				// auto_create is used if this component should be created automatically
				// there are two variations available:
				//
				// array('auto_create' => 'forward'), which can be called by simply using 'auto_create'
				// use this option when a component will be either the first item of a recipe or when the parent component within a recipe is created.
				// parent component
				// - component created
				// - - child component (auto_create)
				// 
				//
				// array('auto_create' => 'reverse')
				// use this option when you want this component to be created when a child component of it is created.
				// parent component
				// - child component (auto_create)
				// - - componnet created
				//
				'auto_create',
				// display title that this component will use within the <title> tag or within breadcrumbs for example
				'title',
				// url allows for a url to be assigned to the component within the recipe.
				// array('url' => 'required') make the url a required field and is useful when auto_create is set
				array('url' => 'required'),
				// there are a number of other pre-defined values that include:
				// 'repudiated_url','role_access','content_create','content_edit','content_delete'
				//  information about these can be found within the public function recipe_fields($recipe) method in class.components.php
			)
		);
	}
	
	
	/*
	 * Component methods used to add content
	 */
	
	
	/*
	 * This method is fired-off on each child components that contains a url of the requested component
	 * $each_component->url = '*some_url*
	 */
	public function as_link($each_component, $vce) {
	
		// $each_component = $this
		// It is an unnecessary duplication, but is meant to make things clearer for those not as familier with OOP.
	
		// in class.components.php this method by default adds an <a> tag that links to the value of $each_component->url
		//
		$title = isset($each_component->title) ? $each_component->title : get_class($this);
		$class_name = strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/',"-$1", get_class($this))) . '-link';
		$class = 'link-container ' . $class_name . ' anchor-tag-' . $each_component->sequence;
		$vce->content->add('main','<div class="' . $class . '"><a href="' . $vce->site->site_url . '/' . $each_component->url . '" title="' . $title . '">' . $title . '</a></div>'  . PHP_EOL);

		// as_link can be used to create a custom link, or can simply return false and prevent any link from being added.
		
		// $vce->content->add() is the method that is used to add content
		// $vce->content->add('*block_name*', '*content*');

	}
	
	/**
	 * This method is fired-off on every component that has been created within a recipe during the "BUILD" process
	 */
	public function as_content($each_component, $vce) {
	
		// $each_component = $this
		
		// to view this component and the meta_data associated with it, use $vce->dump()
		$vce->dump($each_component);
		
		// Standard properties of a component are:
		// 
		// component_id
		// parent_id
		// sequence
		// url (if the component has one associated with it)
		// type (the class name of the component)
		// title
		// created_at (unix timestamp when the component was created)
		// created_by
		
		// along with other meta-data properies that has been supplied during creating or updating a component.
		
		// Additional standard properties of a component are:
		
		// $each_component->parent
		// The parents of the component. The grand-parent of the compoent can be accessed by using $each_component->parent->parent
		
		// $each_component->sub_recipe
		// The recipe for the potential children of the component
		
		// $each_component->recipes
		// the recipe for the potential of this current component
		
		// $each_component->components 
		// the children components of the current component
		
 		// $each_component->dossier
 		// a dossier to create the current component
 		// this is included because of the complexity of a dossier for create, which can include 'auto_create' components
	
	}
	
	/**
	 * 
	 */
	public function as_content_finish($each_component, $vce) {
	
	}
	
	

	/*
	 * This method is called as part of the "GET" process within class.page.php
	 * The "GET" process is where the the requested url is searched for within vce_components,
	 * then the returned component_id is used to search for all parent  and children components.
	 * It is called at the top of the "trunk" within get_components(), which is the component before branching 
	 * It is also called on each component within get_sub_components()
	 */
	public function find_sub_components($requested_component, $vce, $components, $sub_components) {
	
		// $requested_component is the current instantiated class
		// $requested_component = $this
		// It is an unnecessary duplication, but is meant to make things clearer for those not as familier with OOP.
		
		// $vce is the global object within Nestor
		
		// $components is the trunk
		
		// $sub_components are the components after the url
		
		// write to content 
		$vce->content->add('main', 'Sample->find_sub_components()');
		
		// true will allow the GET process to continue and children component will be added to the page object
		// false will prevent any further childern component from being added
		return true;
	
	}

}