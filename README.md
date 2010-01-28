# Kohana Filter Class

Sets up a filtering system where keys from `$_GET` and `$_POST` can be stored in the session to be used to filter data. The advantage to storing these in 
the session is that the filters will remain after a user leaves the page, so that when they come back they won't have to refilter anything.

## Example Usage

*/application/controller/blog.php*

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
						->or_where('body', 'LIKE', $filters->search)
					->where_close()
					->limit($per_page)
					->offset($offset)
					->execute();
				
				$categories = Model::factory('category')->select_list();
			}
		
		}

*/application/views/blog.php*

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

If you set this up for all your pages, you could expect your `$_SESSION` array to look something like this:

	Array
	(
		[filters] => Array
		(
			[blog] => Array
			(
				[index] => Array
				(
					[page]     => 5
					[category] => tutorials
					[search]   => searching
				)
				[articles] => Array
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
					[search]   => NULL
				)
			)
			[users] => Array
			(
				[index] => Array
				(
					[page]     => 4
					[ordering] => NULL
					[search]   => username
				)
			)
		)
	)
	
## Documentation

### Methods

public static *instance (* array $keys, $session_key = 'filters' *)*  
>Creates a singleton instance  
>Add keys to grab from $_GET and $_POST to be used as filters

*$keys* (array) &mdash; key => default to grab from globals  
*$session_key* (string) &mdash; Session key to store filters in

return *Filter*

	$filters = Filter::instance(array(
			'page'     => 1,
			'order'    => 'id_desc',
			'category' => 'news',
		), 'my_filters');

&nbsp;
---

public *__construct (* array $keys, $session_key = 'filters' *)*  
>Sets up the filters environment in the Session

*$keys* (array) &mdash; key => default to grab from globals  
*$session_key* (string) &mdash; Session key to store filters in

return *void*

&nbsp;
---

public *add (* $keys, $value = NULL *)*  
>Add filters  
>$keys can be an array containing keys => defaults

*$keys* (string/array) &mdash; Filter key  
*$value* (mixed) &mdash; Default value

return *Filter*

	$filters->add('state', 'California');
	
	$filters->add(array(
			'state'   => 'California',
			'country' => 'USA',
		));

&nbsp;
---

public *set (* $keys, $value = NULL *)*  
>Set a key manually. Rather than getting from $_GET or $_POST  
>  
>$key can be an array containing keys => values to set multiple keys at once

*$keys* (string/array) &mdash; Filter key  
*$value* (mixed) &mdash; Filter value

return *Filter*

	$filters->set('state', 'Arizona');
	
	$filters->set(array(
			'state'   => $_COOKIE['state'],
			'country' => $object->country,
		));
		
&nbsp;
---

public *get (* $key = NULL, $default = NULL *)*  
>Get a filter, or if no params are given return all local filters

*$key* (string) &mdash; Filter key  
*$default* (mixed) &mdash; Default value if key does not exist

return *mixed* &mdash; Filter value

	$country = $filters->get('country');
	$country = $filters->get('country', 'Canada');
	
	$filters = $filters->get();
	$country = $filters['country'];
	
&nbsp;
---

public *get_global ( )*  
>Get all global filters as an array

return *array* &mdash; All Filters

	$global = $filters->get_global();
	$blog_page = $global['blog']['index']['page'];
	
&nbsp;
---

public *delete (* $keys = NULL *)*  
>Delete filters  
>If no keys are given, it will delete all local filters

*$keys* (string/array) &mdash; One key or an array of keys to delete

return *Filter*

	$filters->delete('page');
	// $filters->page is now undefined
	
	$filters->delete(array('page', 'country', 'category'));
	
&nbsp;
---

public *reset (* $keys = NULL *)*  
>Reset filters to defaults  
>If no keys are given, all keys will be reset

*$keys* (string/array) &mdash; One key or an array of keys to reset

return *Filter*

	$filters->reset();
	// All filters are now reset to their defaults
	
	$filters->reset('page');
	// $filters->page would equal '(integer) 1' because we set that as the default earlier
	
	$filters->reset(array('page', 'country', 'category'));
	
&nbsp;
---

public *__set (* $key, $value *)*  
>Magic function to set a local filter

*$key* (string) &mdash; Filter key  
*$value* (mixed) &mdash; Filter value

return *void*

	$filters->page = 2;
	$filters->country = 'Canada';
	
&nbsp;
---

public *__get (* $key *)*  
>Magic function to get a local filter

*$key* (string) &mdash; Filter key

return *mixed* &mdash; Filter value

	$filters->page = 4;
	echo $filters->page;
	// outputs '4'