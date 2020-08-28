<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MediaGalleryUi\Ui\Component;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Container;
use Magento\Framework\AuthorizationInterface;

/**
 * Directories tree component
 */
class DirectoryTree extends Container
{
    private const ACL_DELETE_FOLDER = 'Magento_MediaGallery::delete_folder';

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UrlInterface $url
     * @param AuthorizationInterface $authorization
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UrlInterface $url,
        AuthorizationInterface $authorization,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->url = $url;
        $this->authorization = $authorization;
    }

    /**
     * @inheritdoc
     */
    public function prepare(): void
    {
        parent::prepare();
        $this->setData(
            'config',
            array_replace_recursive(
                (array) $this->getData('config'),
                [
                    'allowedActions' => $this->getAllowedActions(),
                    'getDirectoryTreeUrl' => $this->url->getUrl('media_gallery/directories/gettree'),
                    'deleteDirectoryUrl' => $this->url->getUrl('media_gallery/directories/delete'),
                    'createDirectoryUrl' => $this->url->getUrl('media_gallery/directories/create')
                ]
            )
        );
    }

    /**
     * Return allowed actions for media gallery
     */
    private function getAllowedActions(): array
    {
        $allowedActions = [];

        if ($this->authorization->isAllowed(self::ACL_DELETE_FOLDER)) {
            $allowedActions[] = 'delete_folder';
        }

        return $allowedActions;
    }
}
