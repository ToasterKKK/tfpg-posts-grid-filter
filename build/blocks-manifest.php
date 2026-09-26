<?php
// This file is generated. Do not modify it manually.
return array(
	'posts-filter' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'tfpg/posts-filter',
		'version' => '1.0.0',
		'title' => 'Posts Filter',
		'category' => 'widgets',
		'icon' => 'filter',
		'description' => 'Category and tag filters for the Posts Grid block. Place it anywhere on the same page as the grid.',
		'keywords' => array(
			'filter',
			'categories',
			'tags',
			'facets'
		),
		'textdomain' => 'tfpg-posts-grid-filter',
		'attributes' => array(
			'showCategories' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showTags' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showCounts' => array(
				'type' => 'boolean',
				'default' => true
			),
			'categoriesLabel' => array(
				'type' => 'string',
				'default' => ''
			),
			'tagsLabel' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'supports' => array(
			'html' => false,
			'color' => array(
				'background' => true,
				'text' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true
			),
			'interactivity' => array(
				'interactive' => true,
				'clientNavigation' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScriptModule' => 'file:../store/index.js',
		'render' => 'file:./render.php'
	),
	'posts-grid' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'tfpg/posts-grid',
		'version' => '1.0.0',
		'title' => 'Posts Grid',
		'category' => 'widgets',
		'icon' => 'grid-view',
		'description' => 'Displays Grid Posts in a responsive grid. Works together with the Posts Filter block placed anywhere on the same page.',
		'keywords' => array(
			'posts',
			'grid',
			'query',
			'filter'
		),
		'textdomain' => 'tfpg-posts-grid-filter',
		'attributes' => array(
			'columns' => array(
				'type' => 'integer',
				'enum' => array(
					2,
					3,
					4
				),
				'default' => 3
			),
			'postsPerPage' => array(
				'type' => 'integer',
				'default' => 6
			)
		),
		'providesContext' => array(
			'tfpg/postsPerPage' => 'postsPerPage'
		),
		'supports' => array(
			'html' => false,
			'align' => array(
				'wide',
				'full'
			),
			'color' => array(
				'background' => true,
				'text' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true
			),
			'interactivity' => array(
				'interactive' => true,
				'clientNavigation' => true
			)
		),
		'example' => array(
			'attributes' => array(
				'columns' => 2,
				'postsPerPage' => 2
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScriptModule' => 'file:../store/index.js',
		'render' => 'file:./render.php'
	),
	'posts-pagination' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'tfpg/posts-pagination',
		'version' => '1.0.0',
		'title' => 'Posts Grid Pagination',
		'category' => 'widgets',
		'icon' => 'ellipsis',
		'parent' => array(
			'tfpg/posts-grid'
		),
		'description' => 'Pagination for the Posts Grid. Respects the active filters.',
		'keywords' => array(
			'pagination',
			'pages',
			'next',
			'previous'
		),
		'textdomain' => 'tfpg-posts-grid-filter',
		'attributes' => array(
			'showPageNumbers' => array(
				'type' => 'boolean',
				'default' => true
			),
			'previousLabel' => array(
				'type' => 'string',
				'default' => ''
			),
			'nextLabel' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'usesContext' => array(
			'tfpg/postsPerPage'
		),
		'supports' => array(
			'html' => false,
			'inserter' => false,
			'reusable' => false,
			'color' => array(
				'background' => true,
				'text' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true
			),
			'layout' => array(
				'allowSwitching' => false,
				'allowInheriting' => false,
				'allowSizingOnChildren' => false,
				'allowVerticalAlignment' => false,
				'default' => array(
					'type' => 'flex',
					'justifyContent' => 'center',
					'flexWrap' => 'wrap'
				)
			),
			'interactivity' => array(
				'interactive' => true,
				'clientNavigation' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'viewScriptModule' => 'file:../store/index.js',
		'render' => 'file:./render.php'
	)
);
