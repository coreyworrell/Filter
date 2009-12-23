# Kohana Filter Class

This class can be used with the Kohana PHP framework to keep `$_GET`, `$_POST`, or any data across multiple page loads,so users can see filtered results w/out having to re-filter.
It can also be used to repopulate form fields when redirecting to avoid submitting a form twice for example.

## Usage

	class Item_Controller extends Controller {
	
		public function before()
		{
			$published = 'Whatever you want for example.'
			
			$this->filters = Filter::instance()
			     ->add('ordering', 'country')
			     ->set('published', $published);
				 
			// The 'add()' method would get 'ordering' and 'country' from $_POST or $_GET
			// The 'set()' method is manually set to the value of '$published'
		}
		
		public function action_index()
		{
			// Get some posts from Database and filter them
			$count = DB::select(DB::expr('COUNT(*) AS count'))->from('posts')->execute()->get('count');
			
			$paging = Pagination::factory(array(
				'total_items'    => $count,
				'items_per_page' => 20,
			));
			
			$posts = DB::select()->from('posts')
				->where('country', '=', $this->filters->country)
				->and_where('published', '=', $this->filters->published)
				->limit($paging->items_per_page)
				->offset($paging->offset)
				->execute();
				
			// $this->filters contains all those filters that were added in the 'before' method.
			
			// Or if you want the filters as an array, simply do this:
			$filter_array = $this->filters->get();
			$ordering = $filter_array['ordering'];
			
			// Or just grab a single filter
			$ordering = $this->filters->get('ordering');
			
			// Or if you need a filter from another page or method, you can
			// grab them all in an array using the method below
			$all_filters = $this->filters->get_global();
		}
		
	}