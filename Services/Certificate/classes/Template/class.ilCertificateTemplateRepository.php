<?php


class ilCertificateTemplateRepository
{
	/**
	 * @var ilDB
	 */
	private $database;

	/**
	 * @var ilLogger
	 */
	private $logger;

	/**
	 * @var null|ObjectHelper
	 */
	private $objectHelper;

	/**
	 * @var ilObjectDataCache|mixed
	 */
	private $objectDataCache;

	/**
	 * @param ilDBInterface $database
	 * @param ilLogger $logger
	 * @param ilObjectDataCache|null $objectDataCache
	 */
	public function __construct(
		\ilDBInterface $database,
		ilLogger $logger = null,
		ilObjectDataCache $objectDataCache = null
	) {
		$this->database = $database;

		if (null === $logger) {
			$logger = $logger = ilLoggerFactory::getLogger('cert');
		}
		$this->logger = $logger;

		if (null === $objectDataCache) {
			global $DIC;
			$objectDataCache = $DIC['ilObjDataCache'];
		}
		$this->objectDataCache = $objectDataCache;
	}

	/**
	 * @param ilCertificateTemplate $certificateTemplate
	 * @throws ilDatabaseException
	 */
	public function save(ilCertificateTemplate $certificateTemplate)
	{
		$this->logger->info('START - Save new certificate template');

		$objId = $certificateTemplate->getObjId();

		$id = $this->database->nextId('certificate_template');

		$this->deactivatePreviousTemplates($objId);

		$columns = array(
			'id'                    => array('integer', $id),
			'obj_id'                => array('integer', $objId),
			'obj_type'              => array('clob', $certificateTemplate->getObjType()),
			'certificate_content'   => array('clob', $certificateTemplate->getCertificateContent()),
			'certificate_hash'      => array('text', $certificateTemplate->getCertificateHash()),
			'template_values'       => array('clob', $certificateTemplate->getTemplateValues()),
			'version'               => array('clob', $certificateTemplate->getVersion()),
			'ilias_version'         => array('clob', $certificateTemplate->getIliasVersion()),
			'created_timestamp'     => array('integer', $certificateTemplate->getCreatedTimestamp()),
			'currently_active'      => array('integer', (integer)$certificateTemplate->isCurrentlyActive()),
			'background_image_path' => array('clob', $certificateTemplate->getBackgroundImagePath()),
		);

		$this->database->insert('certificate_template', $columns);

		$this->logger->info('END - certificate template saved with columns: ', json_encode($columns));
	}

	/**
	 * @param $objId
	 * @return array
	 */
	public function fetchCertificateTemplatesByObjId($objId)
	{
		$this->logger->info(sprintf('START - Fetch multiple certificate templates for object: "%s"', $objId));

		$result = array();

		$sql = '
SELECT * FROM
certificate_template
WHERE obj_id = ' . $this->database->quote($objId, 'integer') . '
ORDER BY version ASC';

		$query = $this->database->query($sql);

		while ($row = $this->database->fetchAssoc($query)) {
			$result[] = new ilCertificateTemplate(
				$row['obj_id'],
				$row['obj_type'],
				$row['certificate_content'],
				$row['certificate_hash'],
				$row['template_values'],
				$row['version'],
				$row['ilias_version'],
				$row['created_timestamp'],
				(boolean) $row['currently_active'],
				$row['background_image_path'],
				$row['id']
			);
		}

		$this->logger->info(sprintf('END - Fetching of certificate templates for object: "%s" with "%s" results', $objId, count($result)));

		return $result;
	}

	/**
	 * @param $objId
	 * @return ilCertificateTemplate
	 * @throws ilException
	 */
	public function fetchCurrentlyActiveCertificate($objId)
	{
		$this->logger->info(sprintf('START - Fetch currently active certificate template for object: "%s"', $objId));

		$sql = '
SELECT * FROM certificate_template
WHERE obj_id = ' . $this->database->quote($objId, 'integer') . '
AND currently_active = 1
';

		$query = $this->database->query($sql);

		while ($row = $this->database->fetchAssoc($query)) {
			$this->logger->info(sprintf('END - Found active certificate for: "%s"', $objId));

			return new ilCertificateTemplate(
				$row['obj_id'],
				$row['obj_type'],
				$row['certificate_content'],
				$row['certificate_hash'],
				$row['template_values'],
				$row['version'],
				$row['ilias_version'],
				$row['created_timestamp'],
				(boolean) $row['currently_active'],
				$row['background_image_path'],
				$row['id']
			);
		}

		$this->logger->info(sprintf('END - Found NO active certificate for: "%s"', $objId));

		return new ilCertificateTemplate(
			$objId,
			$this->objectDataCache->lookUpType($objId),
			'',
			'',
			'',
			0,
			0,
			0,
			true,
			''
		);
	}

	public function fetchPreviousCertificate($objId)
	{
		$this->logger->info(sprintf('START - Fetch previous active certificate template for object: "%s"', $objId));

		$templates = $this->fetchCertificateTemplatesByObjId($objId);

		$resultTemplate = new ilCertificateTemplate(
			$objId,
			$this->objectDataCache->lookUpType($objId),
			'',
			'',
			'',
			0,
			0,
			0,
			true,
			''
		);

		$version = 0;
		foreach ($templates as $template) {
			if ($template->getVersion() > $version) {
				$version = $template->getVersion();
				$resultTemplate = $template;
			}
		}

		$this->logger->info(sprintf('Latest version active certificate template for object: "%s"', $objId));

		return $resultTemplate;
	}

	public function deleteTemplate($templateId, $objectId)
	{
		$this->logger->info(sprintf('START - Delete certificate template("%s") for object: "%s"', $templateId, $objectId));

		$sql = '
DELETE FROM certificate_template
WHERE id = ' . $this->database->quote($templateId, 'integer') . '
AND obj_id = ' . $this->database->quote($objectId, 'integer');

		$query = $this->database->query($sql);

		$this->database->execute($query);

		$this->logger->info(sprintf('END - Delete certificate template("%s") for object: "%s"', $templateId, $objectId));
	}

	public function activatePreviousCertificate($objId)
	{
		$this->logger->info(sprintf('START - Activate previous certificate template for object: "%s"', $objId));

		$certificates = $this->fetchCertificateTemplatesByObjId($objId);

		/** @var ilCertificateTemplate $previousCertificate */
		$previousCertificate = null;
		foreach ($certificates as $certificate) {
			if (null === $previousCertificate) {
				$previousCertificate = $certificate;
			} else if ((int) $certificate->getVersion() > (int) $previousCertificate->getVersion()) {
				$previousCertificate = $certificate;
			}
		}

		$sql = 'UPDATE certificate_template
SET currently_active = 1
WHERE id = ' . $this->database->quote($previousCertificate->getId(), 'integer');

		$query = $this->database->query($sql);

		$this->database->execute($query);

		$this->logger->info(sprintf('END - Previous certificate updated for object: "%s"', $objId));

		return $previousCertificate;
	}

	public function fetchAllObjectIdsByType($type)
	{
		$this->logger->info(sprintf('START - Fetch all object ids for object type: "%s"', $type));

		$sql = 'SELECT DISTINCT obj_id FROM certificate_template WHERE obj_type = ' . $this->database->quote($type, 'text');
		$query = $this->database->query($sql);

		$result = array();
		while ($row = $this->database->fetchAssoc($query)) {
			$result[] = $row['obj_id'];
		}

		$this->logger->info(sprintf('END - All object ids for object type: "%s" in certificate templates: "%s"', $type, json_encode($result)));

		return $result;
	}

	/**
	 * @param $objId
	 * @return ilCertificateTemplate
	 * @throws ilException
	 */
	public function fetchFirstCreatedTemplate(int $objId)
	{
		$this->logger->info(sprintf('START - Fetch first create certificate template for object: "%s"', $objId));

		$sql = 'SELECT * FROM certificate_template
WHERE obj_id = ' . $this->database->quote($objId, 'integer') . '
ORDER BY id ASC LIMIT 1 ';

		$query = $this->database->query($sql);

		while ($row = $this->database->fetchAssoc($query)) {
			$this->logger->info(sprintf('END - Found first create certificate template for object: "%s"', $objId));

			return new ilCertificateTemplate(
				$row['obj_id'],
				$row['obj_type'],
				$row['certificate_content'],
				$row['certificate_hash'],
				$row['template_values'],
				$row['version'],
				$row['ilias_version'],
				$row['created_timestamp'],
				(boolean) $row['currently_active'],
				$row['background_image_path'],
				$row['id']
			);
		}

		throw new ilException('No matching template found. MAY missing DBUpdate. Please check if the correct version is installed.');
	}

	/**
	 * @param $objId
	 * @throws ilDatabaseException
	 */
	private function deactivatePreviousTemplates($objId)
	{
		$this->logger->info(sprintf('START - Deactivate previous certificate template for object: "%s"', $objId));

		$sql = '
UPDATE certificate_template
SET currently_active = 0
WHERE obj_id = ' . $this->database->quote($objId, 'integer');

		$query = $this->database->query($sql);
		$this->database->execute($query);

		$this->logger->info(sprintf('END - Certificate template deactivated for object: "%s"', $objId));
	}
}
