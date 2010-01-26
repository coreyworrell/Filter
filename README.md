# Kohana Filter Class

Sets up a filtering system where keys from $_GET and $_POST can be stored in the session to be used to filter data. The advantage to storing these in 
the session is that the filters will remain after a user leaves the page, so that when they come back they won't have to refilter anything.

## How It Works

Ok, let's say you have a setup with 3 controllers (or pages): _Blog_, _Portfolio_, & _Users_.  
Each page will have filters that you want to keep track of: _page_, _category_, & _search_.  
_page_, _category_, & _search_ are variables that are being collected in `$_POST` or `$_GET` when a user submits a form.

You would set that up something like this:

		class Controller_Blog extends Controller_Template {
			
			public function action_index()
			{
				$this->template->content = View::factory('blog')
					->bind('filters', $filters)
					->bind('posts', $posts)
					->bind('categories', $categories);
				
				$filters = Filter::instance(array(
						'page'     => 1,
						'ordering' => 'id DESC',
						'search'   => NULL,
					));
					
				$per_page = 10;
				$offset = ($filters->page - 1) * $per_page;
					
				$posts = Model::factory('post')
					->where('category_id', '=', $filters->category)
					->where_open()
						->where('title', 'LIKE', $filters->search)
						->where('body', 'LIKE', $filters->search)
					->where_close()
					->limit($per_page)
					->offset($offset)
					->execute();
				
				$categories = Model::factory('category')->select_list();
			}
			
			public function action_display()
			{
				$filters = Filter::instance((
						'page'     => 1,
						'ordering' => 'id ASC'
					));
			}
			
			// Rest of controller actions here or whatever.
		
		}
		
		// Same for Portfolio and Users controllers

And you could set up your view with something like this:

	<h1>Blog</h1>
	
	<?php echo form::open() ?>
		<?php echo form::label('search') ?>
		<?php echo form::input('search', $filters->search) ?>
		
		<?php echo form::select('category', $categories, $filter->category) ?>
		
		<button type="submit">Filter</button>
	</form>
	
	<?php foreach ($posts as $post): ?>
		<div class="post">
			<h2><?php echo $post->title ?></h2>
			<?php echo $post->body ?>
		</div>
	<?php endforeach ?>

Now after a user goes to those pages and submits forms and applies filters, you could expect your `$_SESSION` array to look something like this:

	Array
	(
		[filters] => Array
		(
			[blog] => Array
			(
				[index] => Array
				(
					[page]     => 5
					[parent] => id DESC
					[query]    => searching
				)
				[display] => Array
				(
					[page]     => 1
					[ordering] => id ASC
				)
			)
			[portfolio] => Array
			(
				[index] => Array
				(
					[page]     => 2
					[ordering] => id ASC
					[query]    => NULL
				)
			)
			[users] => Array
			(
				[index] => Array
				(
					[page]     => 4
					[ordering] => NULL
					[query]    => username
				)
			)
		)
	)