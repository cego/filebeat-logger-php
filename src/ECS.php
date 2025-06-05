<?php

namespace Cego;

use Throwable;
use Carbon\Carbon;

/**
 * Elastic Common Schema (ECS) builder.
 *
 * This class provides a structure for building and managing ECS-compliant data.
 * https://www.elastic.co/docs/reference/ecs
 */
class ECS
{
    /**
     * @var array<string, mixed>
     */
    protected $data = [];

    public static function create(): self
    {
        return new self();
    }

    public function withThrowable(Throwable $throwable): self
    {
        $this->data = array_merge($this->data, FilebeatContextProcessor::errorExtras($throwable));

        return $this;
    }

    /**
     * @param string $action The action captured by the event. This describes the information in the event. It is more specific than category.
     * @param string[] $category The category values must be one of the following (https://www.elastic.co/docs/reference/ecs/ecs-allowed-values-event-category) : api, authentication, configuration, database, driver, email, file, host, iam, intrusion_detection, library, malware, network, package, process, registry, session, threat, vulnerability, web
     * @param string $dataset It's recommended but not required to start the dataset name with the module name, followed by a dot, then the dataset name. Example "apache.access"
     * @param string $outcome The outcome of the event. It must be one of the following: success, failure, unknown
     * @param Carbon|null $created The time of the event if relevant
     * @param string|null $code The code of the event if relevant
     * @param int|null $duration The duration of the event in nanoseconds if relevant. Use hrtime(true) for monotonic time in nanoseconds.
     * @param string|null $id The unique identifier of the event if relevant.\
     * @param string|null $reason The reason for the event if relevant. Example: "User was not validated"
     *
     * @return ECS
     */
    public function withEvent(
        string  $action,
        array   $category,
        string  $dataset,
        string  $outcome,
        ?Carbon $created = null,
        ?string $code = null,
        ?int    $duration = null,
        ?string $id = null,
        ?string $reason = null
    ): self {
        $eventData = [
            'action'   => $action,
            'category' => $category,
            'dataset'  => $dataset,
            'outcome'  => $outcome,
        ];

        if ($created !== null) {
            $eventData['created'] = $created->toIso8601ZuluString();
        }

        if ($code !== null) {
            $eventData['code'] = $code;
        }

        if ($duration !== null) {
            $eventData['duration'] = $duration;
        }

        if ($id !== null) {
            $eventData['id'] = $id;
        }

        if ($reason !== null) {
            $eventData['reason'] = $reason;
        }

        $this->data['event'] = array_merge($this->data['event'] ?? [], $eventData);

        return $this;
    }
}
