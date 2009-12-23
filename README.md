# Kohana Filter Class

This class can be used with the Kohana PHP framework to keep `$_GET`, `$_POST`, or any data across multiple page loads,so users can see filtered results w/out having to re-filter.
It can also be used to repopulate form fields when redirecting to avoid submitting a form twice for example.

## How It Works

Ok, let's say you have a setup with 3 controllers (or pages): _Blog_, _Portfolio_, & _Users_.  
Each page will have filters that you want to keep track of: _page_, _ordering_, & _query_.  
_page_, _ordering_, & _query_ are variables that are being collected in `$_POST` or `$_GET` when a user submits a form.

You would set that up something like this:

		class Controller_Blog extends Controller_Template {
			
			public function action_index()
			{
				$filters = Filter::instance()->add('page', 'ordering', 'query');
			}
			
			public function action_display()
			{
				$filters = Filter::instance()->add('page', 'ordering');
			}
			
			// Rest of controller actions here or whatever.
		
		}
		
		// Same for Portfolio and Users controllers

Now after a user goes to those pages and submits forms and applies filters, you could expect your `$_SESSION` array to look something like this:
	Array
	(
		[filters] => Array
		(
			[blog] => Array
			(
				[index] => Array
				(
					[page] => 5
					[ordering] => id_desc
					[query] => searching
				)
				[display] => Array
				(
					[page] => 1
					[ordering] => 
				)
			)
			[portfolio] => Array
			(
				[index] => Array
				(
					[page] => 2
					[ordering] => id_asc
					[query] => 
				)
			)
			[users] => Array
			(
				[index] => Array
				(
					[page] => 4
					[ordering] => 
					[query] => username
				)
			)
		)
	)


## Example Usage

	class Controller_Item extends Controller {
		
		public function action_index()
		{
			$published = 'anything, could be a variable you got from a CURL request, or whatever!';
			
			$filters = Filter::instance()
			     ->add('ordering', 'country')
			     ->set('published', $published);
				 
			$posts = DB::select()->from('posts')
				->where('country', '=', $filters->country)
				->and_where('published', '=', $filters->published)
				->execute();
				
			// $filters contains all the filters available to this action
			
			// Or if you want the filters as an array, simply do this:
			$filter_array = $filters->get();
			$ordering = $filter_array['ordering'];
			
			// Or just grab a single filter
			$ordering = $filters->get('ordering');
			
			// Or if you need a filter from another page or method, you can
			// grab them all in an array using the method below
			$all_filters = $filters->get_global();
		}
		
	}