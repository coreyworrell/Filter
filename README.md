# Kohana Filter Class

Sets up a filtering system where keys from `$_GET` and `$_POST` can be stored in the session to be used to filter data. The advantage to storing these in 
the session is that the filters will remain after a user leaves the page, so that when they come back they won't have to refilter anything.

## Example Usage

**/application/controller/blog.php**

~~~ php
<?php

class Controller_Blog extends Controller_Template {
	
	public function action_index()
	{
		$this->template->content = View::factory('blog')
			->bind('filters', $filters)
			->bind('posts', $posts)
			->bind('categories', $categories);
		
		$filters = Filter::instance(array(
				'page'     => 1,
				'category' => 'default-category',
				'search'   => NULL,
			));
			
		$per_page = 10;
		$offset = ($filters->page - 1) * $per_page;
			
		$posts = Model::factory('post')
			->where('category_name', '=', $filters->category)
			->where_open()
				->where('title', 'LIKE', $filters->search)
				->or_where('body', 'LIKE', $filters->search)
			->where_close()
			->limit($per_page)
			->offset($offset)
			->execute();
		
		$categories = Model::factory('category')->select_list();
	}
	
	public function action_view()
	{
		/* ... */
		
		// We'll reuse the filters from the index action here
		$filters = Filter::instance('blog/index');
		
		/* ... */
	}

}
~~~

**/application/views/blog.php**

~~~ php
<h1>Blog</h1>

<?php echo Form::open() ?>
	<?php echo Form::label('search') ?>
	<?php echo Form::input('search', $filters->search) ?>
	
	<?php echo Form::select('category', $categories, $filter->category) ?>
	
	<button type="submit">Filter</button>
</form>

<?php foreach ($posts as $post): ?>
	<div class="post">
		<h2><?php echo $post->title ?></h2>
		<?php echo $post->body ?>
	</div>
<?php endforeach ?>
~~~

If you set this up for all your pages, you could expect your `$_SESSION` array to look something like this:

	Array
	(
		[filters] => Array
		(
			[blog/index] => Array
			(
				[page]     => 5
				[category] => tutorials
				[search]   => searching
			)
			[blog/articles] => Array
				[page]     => 1
				[ordering] => id ASC
			)
			[portfolio/index] => Array
			(
				[page]     => 2
				[ordering] => id ASC
				[search]   => NULL
			)
			[admin/users/index] => Array
			(
				[page]     => 4
				[ordering] => NULL
				[search]   => username
			)
		)
	)
	
## Documentation

Coming soon. Just take a look at the code and you'll be able to figure it out.