<?
require_once("framework.php");

class Prompt extends Model
{
	public static function variables()
	{
		return array(
					"prompt_id" => new Variable("serial", "!null"),
					"prompt" => new Variable("text", "!null"),
					"description" => new Variable("text"),
					"status" => new Variable("text", "!null"), //online offline
					"file" => new Variable("text", "!null") //real name of the file 
				);
	}

	function __construct()
	{
		parent::__construct();
	}
}
?>