<?php
/**
 * This file contains only the EventCategory class.
 */

declare(strict_types=1);

namespace AppBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContext;

/**
 * An EventCategory is a wiki category tied to an Event.
 * @ORM\Entity(repositoryClass="AppBundle\Repository\EventCategoryRepository")
 * @ORM\Table(
 *     name="event_category",
 *     indexes={
 *         @ORM\Index(name="ec_event", columns={"ec_event_id"})
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="ec_event_domains", columns={"ec_event_id", "ec_category_id", "ec_domain"})
 *     },
 *     options={"engine":"InnoDB"}
 * )
 * @ORM\EntityListeners({"AppBundle\EventSubscriber\CategorySubscriber"})
 */
class EventCategory
{
    /**
     * @ORM\Id
     * @ORM\Column(name="ec_id", type="integer")
     * @ORM\GeneratedValue
     * @var int Unique ID of the EventCategory.
     */
    protected $id;

    /**
     * Many EventCategory's belong to one Event.
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="categories")
     * @ORM\JoinColumn(name="ec_event_id", referencedColumnName="event_id", nullable=false)
     * @var Event Event this EventCategory belongs to.
     */
    protected $event;

    /**
     * @ORM\Column(name="ec_title", type="string", length=255)
     * @Assert\NotBlank(message="")
     * @Assert\Type("string")
     * @Assert\Length(max=255)
     * @var string Category title.
     */
    protected $title;

    /**
     * @ORM\Column(name="ec_category_id", type="integer")
     * @Assert\NotBlank(message="")
     * @var int Category ID. Correlates to cat_id in the 'category' table on the replicas.
     */
    protected $categoryId;

    /**
     * @ORM\Column(name="ec_domain", type="string", length=255, nullable=false)
     * @Assert\Type("string")
     * @Assert\NotBlank(message="")
     * @var string Domain of the wiki, without the .org.
     */
    protected $domain;

    /**
     * EventCategory constructor.
     * @param Event $event
     * @param string $title
     * @param string $domain Without .org, such as en.wikipedia
     */
    public function __construct(Event $event, string $title, string $domain)
    {
        $this->event = $event;
        $this->event->addCategory($this);
        $this->setTitle($title);
        $this->domain = $domain;
    }

    /**
     * Get the ID of the EventCategory.
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the Event this EventCategory belongs to.
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * Set the Event this EventCategory belongs to.
     * @param Event $event
     */
    public function setEvent(Event $event): void
    {
        $this->event = $event;
    }

    /**
     * Get the wiki domain this EventCategory applies to.
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Set the wiki domain this EventCategory applies to.
     * @param string $domain
     */
    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * Set the title of the category.
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        // Use underscores instead of spaces, as they will have to be when querying the replicas.
        $this->title = str_replace(' ', '_', trim($title));
    }

    /**
     * Get the title of the category.
     * @param bool $underscored Set to true when fetching titles for use in database queries.
     * @return string
     */
    public function getTitle(bool $underscored = false): string
    {
        if ($underscored) {
            return $this->title;
        }
        return str_replace('_', ' ', $this->title);
    }

    /**
     * Get the ID of the category. Correlates to cat_id in the 'category' table on the replicas.
     * @return int|null
     */
    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    /**
     * Set the category ID.
     * @param int|null $categoryId Null to invalidate the entity (as $categoryId cannot be null).
     */
    public function setCategoryId(?int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    /***************
     * VALIDATIONS *
     ***************/

    /**
     * Validate the wiki is applicable to the event based on the associated EventWikis.
     * @Assert\Callback
     * @param ExecutionContext $context Supplied by Symfony.
     */
    public function validateWiki(ExecutionContext $context): void
    {
        $regex = $this->event->getAvailableWikiPattern();

        // If it is an invalid WMF wiki (unrelated to the Event), the domain will be blanked and hence
        // a validation will be added from the @Assert\NotBlank on self::domain. Here we only check if it's a valid
        // wiki for the Event, so that we don't include redundant error messages.
        if (1 === preg_match(EventWiki::getValidPattern(), $this->domain) && 1 !== preg_match($regex, $this->domain)) {
            $context->buildViolation('error-unconfigured-wiki')
                ->setParameter(0, $this->domain)
                ->atPath('domain')
                ->addViolation();
        }
    }
}
