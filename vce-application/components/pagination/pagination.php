<?php

class Pagination extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Pagination',
			'description' => 'When sub components is inside this component, sub components are paginated',
			'category' => 'site',
			'recipe_fields' => array('auto_create','title',
			'pagination_limit' => array(
				'label' => array('message' => 'Sub-Components Paginatate','error' => 'Select a Number'),
				'type' => 'select',
				'name' => 'pagination_limit',
				'options' => array('1','5','10','20','30'),
				'data' => array('tag' => 'required')
			)
			)
		);
	}

	/**
	 * things to do when this component is preloaded
	 */
	public function preload_component() {
		
		$content_hook = array (
		'page_build_content_callback' => 'Pagination::paginate_components'
		);

		return $content_hook;

	}

	/**
	 * build_sub_components
	 */
	public static function paginate_components($sub_components, $each_component, $vce) {
	
		if (isset($each_component->pagination_limit) || isset($each_component->recipe['pagination_limit'])) {
		
			$pagination_limit = isset($each_component->pagination_limit) ? $each_component->pagination_limit : $each_component->recipe['pagination_limit'];
		
			$vce->pagination_total = count($sub_components);
			$vce->pagination_pages = ceil(count($sub_components) / $pagination_limit);
			$vce->pagination_offset = isset($vce->pagination_offset) ? $vce->pagination_offset : 1;
					
			$pagination_offset = ($vce->pagination_offset != 1) ? ($pagination_limit * ($vce->pagination_offset - 1)) : 0;
					
			// use array_slice to limit sub components recursively passed back to build_content
			$sub_components = array_slice($sub_components, $pagination_offset, $pagination_limit);
		}
		
		return $sub_components;
		
	}

		
	/**
	 * book end of as_content
	 */
	public function as_content_finish($each_component, $vce) {

		if ($vce->pagination_pages > 1) {
		
			// set pagination_offset value so that we arrive there again
			$vce->site->add_attributes('pagination_offset',$vce->pagination_offset);
		
			// create a special dossier
			$dossier_for_show = $vce->generate_dossier(array('type' => 'Pagination','procedure' => 'show'));		

			$content = '<div class="pagination">';
		
			$content .= '<div>' . $vce->pagination_offset . ' of ' . $vce->pagination_pages . '</div>';

			for ($x = 1;$x <= $vce->pagination_pages; $x++) {

				$class = ($x == $vce->pagination_offset) ? 'class="highlighted"': '';

				$content .= <<<EOF
<form class="inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_show">
<input type="hidden" name="pagination_offset" value="$x">
<input $class type="submit" value="$x">
</form>
EOF;
		
			}
		
			$content .= '</div>';

			$vce->content->add('main',$content);
		
		}
	
	}
	
	
	/**
	 * show
	 */
	public function show($input) {
	
		$site = $this->vce->site;
		
		$site->add_attributes('pagination_offset',$input['pagination_offset']);
	
		echo json_encode(array('response' => 'success','action' => 'reload','delay'=>'0'));
		return;
	
	}

}