<?php namespace October\Rain\Database\Traits;

use October\Rain\Database\Models\Draft;
use October\Rain\Database\Scopes\DraftableScope;

/**
 * Draftable trait allows draft versions of models
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait Draftable
{
    /**
     * @var string|null draftableSaveMode for saving the draft.
     */
    protected $draftableSaveMode;

    /**
     * @var array|null draftableSaveAttrs contains draft notes
     */
    protected $draftableSaveAttrs = [];

    /**
     * bootDraftable trait for a model.
     */
    public static function bootDraftable()
    {
        static::addGlobalScope(new DraftableScope);

        static::extend(function ($model) {
            $model->bindEvent('model.saveInternal', function() use ($model) {
                $model->draftableSaveModeInternal();
            });
        });
    }

    /**
     * saveFirstDraft
     */
    public function saveFirstDraft(array $attrs = [])
    {
        $this->{$this->getDraftModeColumn()} = DraftableScope::MODE_NEW_UNSAVED;

        $this->save(['force' => true]);

        $draft = $this->{$this->getDraftableNotesName()};

        $draft->fill($attrs);

        $draft->save();
    }

    /**
     * setDraftAutosave
     */
    public function setDraftAutosave(array $attrs): void
    {
        $this->draftableSaveMode = null;
        $this->draftableSaveAttrs = $attrs;
    }

    /**
     * setDraftCommit
     */
    public function setDraftCommit(array $attrs): void
    {
        $this->draftableSaveMode = DraftableScope::MODE_NEW_SAVED;
        $this->draftableSaveAttrs = $attrs;
    }

    /**
     * setDraftPublish
     */
    public function setDraftPublish(): void
    {
        $this->draftableSaveAttrs = [];
        $this->draftableSaveMode = DraftableScope::MODE_PUBLISHED;
    }

    /**
     * isDraftStatus
     */
    public function isDraftStatus()
    {
        return $this->{$this->getDraftModeColumn()} !== DraftableScope::MODE_PUBLISHED;
    }

    /**
     * draftableSaveModeInternal
     */
    public function draftableSaveModeInternal(): void
    {
        $draft = $this->{$this->getDraftableNotesName()};

        if ($this->draftableSaveAttrs) {
            $draft->fill($this->draftableSaveAttrs);
            $draft->save();
        }

        if ($this->draftableSaveMode === DraftableScope::MODE_PUBLISHED && $draft->exists) {
            $draft->delete();
        }

        if ($this->draftableSaveMode) {
            $this->{$this->getDraftModeColumn()} = $this->draftableSaveMode;
        }
    }

    /**
     * getDraftableNoteName
     */
    public function getDraftableNotesName(): string
    {
        return 'draft_record';
    }

    /**
     * getDraftModeColumn gets the name of the "draft_mode" column.
     */
    public function getDraftModeColumn(): string
    {
        return 'draft_mode';
    }

    /**
     * getQualifiedDraftModeColumn gets the fully qualified "draft_mode" column.
     */
    public function getQualifiedDraftModeColumn(): string
    {
        return $this->getTable().'.'.$this->getDraftModeColumn();
    }
}
