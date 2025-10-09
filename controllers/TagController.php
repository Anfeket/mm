<?php
require_once __DIR__ . '/../models/Tag.php';

class TagController
{
	public function search()
	{
		error_log(print_r($_GET, true));
		$query = $_GET['search'] ?? '';
		$category = $_GET['category'] ?? null;

		if (trim($query) === '') {
			echo json_encode([]);
			return;
		}

		$tags = Tag::search($query, $category);
		header('Content-Type: application/json');
		echo json_encode($tags);
	}
}
