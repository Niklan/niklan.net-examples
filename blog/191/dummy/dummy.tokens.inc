<?php

/**
 * @file
 * File for custom token hooks.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function dummy_token_info() {
  $node['title-reverse'] = [
    'name' => t('Title reversed'),
    'description' => t('Reverse title of the node.'),
  ];

  $node['next-node'] = [
    'name' => t('Next node'),
    'description' => t('Next node of the same type (bundle).'),
    'type' => 'node',
  ];

  $node['previous-node'] = [
    'name' => t('Previous node'),
    'description' => t('Previous node of the same type (bundle).'),
    'type' => 'node',
  ];

  $node['title-string'] = [
    'name' => t('Title string'),
    'description' => t('Title passed to my_custom_type tokens.'),
    'type' => 'my_custom_type',
  ];

  $random['node'] = [
    'name' => t('Random node'),
    'description' => t('Return random published node.'),
    'type' => 'node',
  ];

  $my_custom_type = [
    'name' => t('My custom type'),
    'description' => t('Custom token processors.'),
    'needs-data' => 'my_custom_type',
  ];

  $my_custom_type_tokens['strlen'] = [
    'name' => t('String length'),
    'description' => t('Number characters in string.'),
    'dynamic' => TRUE,
  ];

  $my_custom_type_tokens['uppercase'] = [
    'name' => t('Uppercase string'),
    'description' => t('Cast string to uppercase.'),
  ];

  return [
    'types' => ['my_custom_type' => $my_custom_type],
    'tokens' => [
      'node' => $node,
      'random' => $random,
      'my_custom_type' => $my_custom_type_tokens,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function dummy_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();
  $replacements = [];

  if ($type == 'node' && !empty($data['node'])) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'title-reverse':
          $replacements[$original] = strrev($node->label());
          break;

        case 'next-node':
          $next_node = _dummy_find_next_node($node);
          if ($next_node) {
            $replacements[$original] = $next_node->label();
            $bubbleable_metadata->addCacheableDependency($next_node);
          }
          break;

        case 'previous-node':
          $prev_node = _dummy_find_previous_node($node);
          if ($prev_node) {
            $replacements[$original] = $prev_node->label();
            $bubbleable_metadata->addCacheableDependency($prev_node);
          }
          break;

        case 'title-string':
          $replacements[$original] = $node->label();
          break;
      }
    }

    // [node:next-node] chained token.
    if ($next_node_tokens = $token_service->findWithPrefix($tokens, 'next-node')) {
      if ($next_node = _dummy_find_next_node($node)) {
        $bubbleable_metadata->addCacheableDependency($next_node);
        $replacements += $token_service->generate('node', $next_node_tokens, ['node' => $next_node], $options, $bubbleable_metadata);
      }
    }

    // [node:previous-node] chained token.
    if ($prev_node_tokens = $token_service->findWithPrefix($tokens, 'previous-node')) {
      if ($prev_node = _dummy_find_previous_node($node)) {
        $bubbleable_metadata->addCacheableDependency($prev_node);
        $replacements += $token_service->generate('node', $prev_node_tokens, ['node' => $prev_node], $options, $bubbleable_metadata);
      }
    }

    // [node:title-string].
    if ($title_string_tokens = $token_service->findWithPrefix($tokens, 'title-string')) {
      $replacements += $token_service->generate('my_custom_type', $title_string_tokens, ['my_custom_type' => $node->label()], $options, $bubbleable_metadata);
    }
  }

  if ($type == 'random') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'node':
          if ($random_node = _dummy_random_node()) {
            $replacements[$original] = $random_node->label();
            $bubbleable_metadata->addCacheableDependency($random_node);
          }
          break;
      }
    }

    // [random:node] chained token.
    if ($random_node_tokens = $token_service->findWithPrefix($tokens, 'node')) {
      if ($random_node = _dummy_random_node()) {
        $bubbleable_metadata->addCacheableDependency($random_node);
        $replacements += $token_service->generate('node', $random_node_tokens, ['node' => $random_node], $options, $bubbleable_metadata);
      }
    }
  }

  if ($type == 'my_custom_type') {
    if (!empty($data['my_custom_type'])) {
      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'uppercase':
            $replacements[$original] = mb_strtoupper($data['my_custom_type']);
            break;

          case 'strlen':
            $replacements[$original] = strlen($data['my_custom_type']);
            break;
        }
      }
    }

    // [my_custom_type:strlen:?].
    if ($strlen_tokens = $token_service->findWithPrefix($tokens, 'strlen')) {
      foreach ($strlen_tokens as $string => $original) {
        $replacements[$original] = strlen($string);
      }
    }
  }

  return $replacements;
}
