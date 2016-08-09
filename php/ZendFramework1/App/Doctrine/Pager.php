<?php
/**
 * Application specific Doctrine Pager
 *
 * @author Steven Hill Sr ( steven.hill.sr at gmail com )
 */

class App_Doctrine_Pager {
  
  public function generatePage($query, $currentPage = 1, $perPageCount = 25)
  {
        $pager = new Doctrine_Pager(
          $query,
          $currentPage,
          $perPageCount
      );
        

      $page = array();

      $page['grid2'] = $pager->execute(array(),Doctrine::HYDRATE_ARRAY);

//      error_log('App_Doctrine_Pager - generatePage - pager results: ' . print_r($page['grid2'],TRUE));

      $page['grid'] = array();
      foreach($page['grid2'] as $key => $value) {
          $page['grid']['row'.$key] = $value;
      }

      unset($page['grid2']);

      $pager_range  = $pager->getRange('Sliding', array('chunk' => 5));

      $pager_layout = new Doctrine_Pager_Layout($pager, $pager_range, '?newPage={%page_number}');
      $pager_layout->setTemplate('[<a href="{%url}">{%page}</a>]');
      $pager_layout->setSelectedTemplate('[{%page}]');

      $prev_pages = null;
      $next_pages = null;

      if ($currentPage > 1) {
          $prev_pages  = '[<a href="?newPage='.$pager->getFirstPage().'" style="cursor: pointer; text-decoration: underline">&lt;&lt;]';
          $prev_pages .= '[<a href="?newPage='.$pager->getPreviousPage().'" style="cursor: pointer; text-decoration: underline">&lt;]';
      }

      if ($currentPage < $pager->getLastPage() && $pager->getLastPage() > 5) {
          $next_pages  = '[<a href="?newPage='.$pager->getNextPage().'" style="cursor: pointer; text-decoration: underline">&gt;]';
          $next_pages .= '[<a href="?newPage='.$pager->getLastPage().'" style="cursor: pointer; text-decoration: underline">&gt;&gt;]';
      }

      if ($pager->getLastPage() == 1) {
        $page['pagination'] = '';
      } else {
        $page['pagination'] = $prev_pages . $pager_layout->display(array(), true) . $next_pages;
      }

  //    $page['pagination'] = $pager_layout->display(array(), true);


      return $page;
  }

}
