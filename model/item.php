<?php namespace Model;

class item extends \app\model {
  const SRC  = '../data/item.xml';
  const PATH = '/items/item';
  
  protected function fixture(): array  {
    return [];
  }
}