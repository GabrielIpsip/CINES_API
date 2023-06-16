<?php


namespace App\Common\Traits;


use App\Entity\Contents;
use App\Entity\ContentTypes;
use App\Entity\Languages;
use App\Entity\Translations;
use App\Utils\StringTools;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait TranslationsTrait
{
    /**
     * Add new translation in database.
     * @param ?array $pairLangValues Array with languages and values. ex: [{"lang": "string","value": "string"},...]
     * @param string $tableName Table name will link translated value;
     * @return Contents Content object to link table which must be translated.
     * @throws Exception HTTP ERROR 404 or 400
     */
    public function addTranslation(?array $pairLangValues, string $tableName) : ?Contents
    {
        if (!$pairLangValues)
        {
            return null;
        }

        if (!$this->checkIfDefaultLanguage($pairLangValues))
        {
            throw new Exception("Default language : " . self::DEFAULT_LANG. " is required.",
                Response::HTTP_BAD_REQUEST);
        }

        $doctrine = $this->managerRegistry;
        $em = $doctrine->getManager();
        $contentType = null;
        $content = null;

        foreach ($pairLangValues as $pair)
        {
            $lang = $pair["lang"];
            $value = $pair["value"];
            $lg = $doctrine->getRepository(Languages::class)->findOneBy(array('code' => $lang));

            if (!$lg)
            {
                throw new Exception("No language '" . $lang . "' in language table",
                    Response::HTTP_NOT_FOUND);
            }

            if (!$contentType && !$content)
            {
                $contentTypeName = $this->buildContentTypeName($value, $tableName);
                $contentType = new ContentTypes($contentTypeName);
                $em->persist($contentType);
                $em->flush();

                $content = new Contents($contentType);
                $em->persist($content);
                $em->flush();
            }

            $translation = new Translations($value, $content, $lg);
            $em->persist($translation);
            $em->flush();
        }
        return $content;
    }

    /**
     * Get translation in database.
     * @param string $lang Language code
     * @param ?Contents $content Contents field of entity can be translated.
     * @return string Translated field.
     */
    public function getTranslation(string $lang, ?Contents $content): ?string
    {
        $doctrine = $this->managerRegistry;
        $language = $doctrine->getRepository(Languages::class)
            ->findOneBy(array('code' => $lang));
        if (!$language)
        {
            $language = $this->getDefaultLanguage();
        }

        $value = $doctrine->getRepository(Translations::class)
            ->findOneBy(array('content' => $content,
                'language' => $language));
        if (!$value)
        {
            $language = $this->getDefaultLanguage();
            $value = $doctrine->getRepository(Translations::class)
                ->findOneBy(array('content' => $content, 'language' => $language));
        }

        if (!$value)
        {
            return null;
        }

        return $value->getValue();
    }

    /**
     * Update translation in database.
     * @param array|null $pairLangValues Array with languages and values. ex: [{"lang": "string","value": "string"},...]
     * @param ?Contents $content object to link table which must be translated.
     * @param string $tableName Table name will link translated value;
     * @throws Exception
     */
    public function updateTranslation(?array $pairLangValues, ?Contents $content, string $tableName)
    {
        if ($pairLangValues == null || $content == null) {
            return;
        }

        $doctrine = $this->managerRegistry;
        $em = $doctrine->getManager();
        $contentType = $content->getType();
        $updated = false;

        foreach ($pairLangValues as $pair)
        {
            $lang = $pair["lang"];
            $value = $pair["value"];
            $lg = $doctrine->getRepository(Languages::class)->findOneBy(array('code' => $lang));

            if (!$lg)
            {
                throw new Exception("No language '" . $lang . "' in language table",
                    Response::HTTP_NOT_FOUND);
            }

            if (!$updated)
            {
                $contentType->setName($this->buildContentTypeName($value, $tableName));
                $content->setType($contentType);
                $updated = true;
                $em->flush();
            }

            $existingTranslation = $doctrine->getRepository(Translations::class)
                ->findOneBy(array('language' => $lg, 'content' => $content));

            if ($existingTranslation)
            {
                $existingTranslation->setValue($value);
            }
            else
            {
                $translation = new Translations($value, $content, $lg);
                $em->persist($translation);
            }
            $em->flush();
        }
    }

    private function getDefaultLanguage(): Languages
    {
        return $this->managerRegistry->getRepository(Languages::class)
            ->findOneBy(array('code' => self::DEFAULT_LANG));
    }

    private function buildContentTypeName(string $value, string $tableName): string
    {
        $name = $tableName . "_" . StringTools::cleanString($value);
        $name = mb_substr($name, 0, 50);
        return $name;
    }

    private function checkIfDefaultLanguage(array $pairLangValues): bool
    {
        foreach ($pairLangValues as $pair)
        {
            if ($pair['lang'] === self::DEFAULT_LANG)
            {
                return true;
            }
        }
        return false;
    }
}
