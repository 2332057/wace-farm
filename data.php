<?php

$tile_types = [

  // Land Types

  'grass' => [
    'name' => '草',
    'rank' => 1,
    'cost' => 0,
    'color' => '#7cfc00',
    'type' => 'land',
  ],
  'dirt_path' => [
    'name' => '土道',
    'rank' => 1,
    'cost' => 10,
    'color' => '#deb887',
    'type' => 'land',
  ],
  'stone_path' => [
    'name' => '石道',
    'rank' => 1,
    'cost' => 10,
    'color' => '#aba9a6ff',
    'type' => 'land',
  ],
  'water' => [
    'name' => '水',
    'rank' => 1,
    'cost' => 50,
    'color' => '#47d3faff',
    'type' => 'land',
  ],
  'field' => [
    'name' => '畑',
    'rank' => 1,
    'cost' => 50,
    'color' => '#8b4513',
    'type' => 'land',
  ],

  // Crop Types

  'carrot' => [
    'name' => 'にんじん',
    'rank' => 1,
    'cost' => 15,
    'sell_price' => 45,
    'color' => '#8b4513',
    'type' => 'crop',
    'growth_turns' => 3,
  ],
  'wheat' => [
    'name' => '小麦',
    'rank' => 1,
    'cost' => 25,
    'sell_price' => 70,
    'color' => '#8b4513',
    'type' => 'crop',
    'growth_turns' => 4,
  ],
  'potato' => [
    'name' => 'じゃがいも',
    'rank' => 1,
    'cost' => 35,
    'sell_price' => 100,
    'color' => '#8b4513',
    'type' => 'crop',
    'growth_turns' => 5,
  ],

  // Actions

  'harvest' => [
    'name' => '収穫',
    'rank' => 1,
    'cost' => 0,
    'color' => '#ffdf00',
    'type' => 'action',
  ],
];
