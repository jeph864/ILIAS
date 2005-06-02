<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

/**
* Class ilFulltextLMContentSearch
*
* class for searching meta 
*
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id
* 
* @package ilias-search
*
*/
include_once 'Services/Search/classes/class.ilLMContentSearch.php';

class ilFulltextLMContentSearch extends ilLMContentSearch
{

	/**
	* Constructor
	* @access public
	*/
	function ilFulltextLMContentSearch(&$qp_obj)
	{
		parent::ilLMContentSearch($qp_obj);
	}

	function __createWhereCondition()
	{
		// IN BOOLEAN MODE
		if($this->db->isMysql4_0OrHigher())
		{
			$where .= " WHERE MATCH(content) AGAINST('";
			$prefix = $this->query_parser->getCombination() == 'and' ? '+' : '';
			foreach($this->query_parser->getWords() as $word)
			{
				$where .= $prefix;
				$where .= $word;
				$where .= '* ';
			}
			$where .= "' IN BOOLEAN MODE) ";
		}
		else
		{
			// Mysql 3.23
			$where .= " WHERE ";
			$counter = 0;
			foreach($this->query_parser->getWords() as $word)
			{
				if($counter++)
				{
					$where .= strtoupper($this->query_parser->getCombination());
				}
				$where .= " MATCH (content) AGAINST('";
				$where .= $word;
				$where .= "') ";
			}
		}
		return $where;
	}		
}
?>
