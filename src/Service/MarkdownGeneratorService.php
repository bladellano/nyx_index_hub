<?php

namespace Drupal\nyx_index_hub\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Serviço para gerar Markdown a partir de dados de nodes.
 */
class MarkdownGeneratorService {

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Construtor.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->logger = $logger_factory->get('nyx_index_hub');
  }

  /**
   * Gera Markdown para um único node.
   *
   * @param array $node_data
   *   Dados do node serializados.
   *
   * @return string
   *   Conteúdo em formato Markdown.
   */
  public function generateFromNode(array $node_data): string {
    $markdown = [];

    // Título principal
    $markdown[] = '# ' . ($node_data['title'] ?? 'Sem título');
    $markdown[] = '';

    // Metadados básicos
    if (!empty($node_data['bundle_label'])) {
      $markdown[] = '**Tipo:** ' . $node_data['bundle_label'];
    }

    if (!empty($node_data['created'])) {
      $markdown[] = '**Criado:** ' . date('Y-m-d H:i:s', $node_data['created']);
    }

    if (!empty($node_data['changed']) && $node_data['changed'] !== $node_data['created']) {
      $markdown[] = '**Atualizado:** ' . date('Y-m-d H:i:s', $node_data['changed']);
    }

    $markdown[] = '';
    $markdown[] = '---';
    $markdown[] = '';

    // Campos do node
    if (!empty($node_data['fields']) && is_array($node_data['fields'])) {
      foreach ($node_data['fields'] as $field) {
        if (!empty($field['label']) && !empty($field['value'])) {
          $markdown[] = '## ' . $field['label'];
          $markdown[] = '';
          $markdown[] = $field['value'];
          $markdown[] = '';
        }
      }
    }

    return implode("\n", $markdown);
  }

  /**
   * Gera Markdown consolidado para múltiplos nodes.
   *
   * @param array $nodes_data
   *   Array de dados de nodes serializados.
   * @param string|null $content_type_label
   *   Label do tipo de conteúdo (opcional).
   *
   * @return string
   *   Conteúdo consolidado em formato Markdown.
   */
  public function generateFromMultipleNodes(array $nodes_data, ?string $content_type_label = NULL): string {
    if (empty($nodes_data)) {
      $this->logger->warning('generateFromMultipleNodes: Array de nodes vazio');
      return '';
    }

    $markdown = [];

    // Cabeçalho principal do documento
    $label = $content_type_label ?? ($nodes_data[0]['bundle_label'] ?? 'Conteúdo');
    $markdown[] = '# ' . $label;
    $markdown[] = '';
    $markdown[] = '**Total de itens:** ' . count($nodes_data);
    $markdown[] = '**Atualizado em:** ' . date('Y-m-d H:i:s');
    $markdown[] = '';
    $markdown[] = '---';
    $markdown[] = '';

    // Índice
    $markdown[] = '## Índice';
    $markdown[] = '';
    foreach ($nodes_data as $node_data) {
      $title = $node_data['title'] ?? 'Sem título';
      $nid = $node_data['nid'] ?? 'unknown';
      $markdown[] = '- [' . $title . '](#node-' . $nid . ')';
    }
    $markdown[] = '';
    $markdown[] = '---';
    $markdown[] = '';

    // Conteúdo de cada node
    foreach ($nodes_data as $index => $node_data) {
      if ($index > 0) {
        $markdown[] = '';
        $markdown[] = '---';
        $markdown[] = '';
      }

      // Título com âncora
      $nid = $node_data['nid'] ?? 'unknown';
      $markdown[] = '<a id="node-' . $nid . '"></a>';
      $markdown[] = '';
      $markdown[] = '## ' . ($node_data['title'] ?? 'Sem título');
      $markdown[] = '';

      // Metadados
      $markdown[] = '**ID:** ' . $nid;

      if (!empty($node_data['created'])) {
        $markdown[] = '**Criado:** ' . date('Y-m-d H:i:s', $node_data['created']);
      }

      if (!empty($node_data['changed']) && $node_data['changed'] !== $node_data['created']) {
        $markdown[] = '**Atualizado:** ' . date('Y-m-d H:i:s', $node_data['changed']);
      }

      $markdown[] = '';

      // Campos do node
      if (!empty($node_data['fields']) && is_array($node_data['fields'])) {
        foreach ($node_data['fields'] as $field) {
          if (!empty($field['label']) && !empty($field['value'])) {
            $markdown[] = '### ' . $field['label'];
            $markdown[] = '';
            $markdown[] = $field['value'];
            $markdown[] = '';
          }
        }
      }
    }

    return implode("\n", $markdown);
  }

}
