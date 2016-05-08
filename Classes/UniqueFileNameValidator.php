<?php
namespace Flownative\Neos\UniqueFileNames;

use Doctrine\ORM\Query;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\I18n\Translator;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Validation\Validator\AbstractValidator;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Validator\AssetValidatorInterface;
use TYPO3\Neos\Controller\BackendUserTranslationTrait;

class UniqueFileNameValidator extends AbstractValidator implements AssetValidatorInterface
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
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $entityManager;

    /**
     * Check if $value is valid. If it is not valid, needs to add an errorw
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
            'SELECT a FROM TYPO3\Media\Domain\Model\Asset a JOIN a.resource r WHERE (a.title = :fileName OR r.filename = :fileName) AND a.Persistence_Object_Identifier != :assetIdentifier'
        );

        $query->setParameter('fileName', $fileName);
        $query->setParameter('assetIdentifier', $this->persistenceManager->getIdentifierByObject($value));

        $result = $query->getArrayResult();

        // We need to exclude ImageVariant objects, but can not do that in the DQL query
        $result = array_filter($result, function($value) {
            return $value['dtype'] !== 'typo3_media_imagevariant';
        });

        if (count($result) > 0) {
            $this->addError(
                $this->translator->translateById('assetWithTitleAlreadyExists', [$fileName], null, $this->_localizationService->getConfiguration()->getCurrentLocale(), 'Main', 'Flownative.Neos.UniqueFileNames'),
                1462705529
            );
        }
    }

}
