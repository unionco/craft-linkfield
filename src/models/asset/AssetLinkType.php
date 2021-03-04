<?php

namespace lenz\linkfield\models\asset;

use Craft;
use craft\elements\Asset;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\models\element\ElementLinkType;
use yii\base\InvalidConfigException;

/**
 * Class AssetLinkType
 */
class AssetLinkType extends ElementLinkType
{
  /**
   * @inheritDoc
   */
  const MODEL_CLASS = AssetLink::class;


  /**
   * AssetLinkType constructor.
   * @param array $config
   */
  public function __construct($config = []) {
    parent::__construct(array_merge($config, [
      'elementType' => Asset::class,
    ]));
  }

  /**
   * @inheritDoc
   */
  public function getSettingsHtml(LinkField $field): string {
    return Craft::$app->view->renderTemplate(
      'typedlinkfield/_settings-element',
      [
        'linkType' => $this,
        'sources' => self::toFolderSources($this->sources),
      ]
    );
  }

  /**
   * @inheritDoc
   */
  public function setSettings(array $settings) {
    parent::setSettings($settings);
    $this->sources = self::toVolumeSources($this->sources);
  }


  // Protected methods
  // -----------------

  /**
   * @return string|null
   */
  protected function getEnabledSources() {
    return $this->sources === '*'
      ? null
      : self::toFolderSources($this->sources);
  }

  /**
   * @return array
   */
  protected function getValidSources(): array {
    $sources = parent::getValidSources();

    return array_merge(
      $sources,
      self::toVolumeSources($sources)
    );
  }


  // Private static methods
  // ----------------------

  /**
   * @param mixed $sources
   * @return mixed
   */
  private static function toVolumeSources($sources) {
    if (is_array($sources)) {
      foreach ($sources as &$source) {
        $source = self::toVolumeSource($source);
      }
    }

    return $sources;
  }


  /**
   * Convert a folder:UID source key to a volume:UID source key.
   *
   * Copied from `craft\fields\Assets::_folderSourceToVolumeSource` as it's private.
   *
   * @param mixed $source
   * @return string
   * @noinspection DuplicatedCode
   */
  private static function toVolumeSource($source): string {
    if ($source && is_string($source) && strpos($source, 'folder:') === 0) {
      $parts = explode(':', $source);
      $folder = Craft::$app->getAssets()->getFolderByUid($parts[1]);

      if ($folder) {
        try {
          $volume = $folder->getVolume();
          return 'volume:' . $volume->uid;
        } catch (InvalidConfigException $e) {
          // The volume is probably soft-deleted. Just pretend the folder didn't exist.
        }
      }
    }

    return (string)$source;
  }

  /**
   * @param mixed $sources
   * @return mixed
   */
  private static function toFolderSources($sources) {
    if (is_array($sources)) {
      foreach ($sources as &$source) {
        $source = self::toFolderSource($source);
      }
    }

    return $sources;
  }

  /**
   * Convert a volume:UID source key to a folder:UID source key.
   *
   * Copied from `craft\fields\Assets::_volumeSourceToFolderSource` as it's private.
   *
   * @param mixed $source
   * @return string
   * @noinspection DuplicatedCode
   */
  private static function toFolderSource($source): string {
    if ($source && is_string($source) && strpos($source, 'volume:') === 0) {
      $parts = explode(':', $source);
      $volume = Craft::$app->getVolumes()->getVolumeByUid($parts[1]);

      if ($volume && $folder = Craft::$app->getAssets()->getRootFolderByVolumeId($volume->id)) {
        return 'folder:' . $folder->uid;
      }
    }

    return (string)$source;
  }
}
