<?php
namespace Flownative\Neos\UniqueFilenames;

use Doctrine\Common\Persistence\ObjectManager as DoctrineObjectManager;
use Doctrine\ORM\Query;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Validation\Validator\AbstractValidator;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Validator\AssetValidatorInterface;
use Neos\Neos\Controller\BackendUserTranslationTrait;

class UniqueFilenameValidator extends AbstractValidator implements AssetValidatorInterface
{
    use BackendUserTranslationTrait;

    /**
     * @Flow\Inject
     * @var Translator
     */
    protected $translator;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Doctrine's Entity Manager. Note that "ObjectManager" is the name of the related
     * interface ...
     *
     * @Flow\Inject
     * @var DoctrineObjectManager
     */
    protected $entityManager;

    /**
     * Check if $value is valid. If it is not valid, needs to add an error
     * to Result.
     *
     * @param AssetInterface $value
     * @return void
     */
    protected function isValid($value)
    {
        $fileName = $value->getTitle() ?:$value->getResource()->getFilename();

        /** @var Query $query */
        $query = $this->entityManager->createQuery(
            'SELECT a FROM Neos\Media\Domain\Model\Asset a JOIN a.resource r WHERE (a.title = :fileName OR r.filename = :fileName) AND a.Persistence_Object_Identifier != :assetIdentifier'
        );

        $query->setParameter('fileName', $fileName);
        $query->setParameter('assetIdentifier', $this->persistenceManager->getIdentifierByObject($value));

        $result = $query->getArrayResult();

        // We need to exclude ImageVariant objects, but can not do that in the DQL query
        $result = array_filter($result, function($value) {
            return $value['dtype'] !== 'neos_media_imagevariant';
        });

        if (count($result) > 0) {
            $this->addError(
                $this->translator->translateById('assetWithTitleAlreadyExists', [$fileName], null, $this->_localizationService->getConfiguration()->getCurrentLocale(), 'Main', 'Flownative.Neos.UniqueFilenames'),
                1462705529
            );
        }
    }

}
