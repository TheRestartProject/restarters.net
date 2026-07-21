<script setup>
import { computed } from 'vue'
import moment from 'moment'

// One Talk topic row (legacy resources/js/components/DiscourseTopic.vue): the
// title links to the Discourse topic, a category badge (colour dot + name)
// sits below, and the comment count + "created ... ago" show on desktop.
const props = defineProps({
  topic: { type: Object, required: true },
  discourseBaseUrl: { type: String, default: '' },
})

const url = computed(() => `${props.discourseBaseUrl}/t/${props.topic.slug}`)

// Legacy used discourseBaseUrl + category.topic_url (a relative path Discourse
// supplies); guard the whole href when either piece is missing.
const categoryUrl = computed(() => {
  const path = props.topic.category?.topic_url
  return path ? `${props.discourseBaseUrl}${path}` : null
})

const ago = computed(() => (props.topic.created_at ? moment(props.topic.created_at).fromNow() : ''))
</script>

<template>
  <tr class="talk-topic" :data-testid="`talk-topic-${topic.id}`">
    <td class="talk-topic__main">
      <a :href="url" :data-testid="`talk-topic-link-${topic.id}`">{{ topic.title }}</a>
      <div v-if="topic.category">
        <a class="talk-topic__badge" :href="categoryUrl">
          <span class="talk-topic__badge-dot" :style="{ backgroundColor: `#${topic.category.color}` }" />
          <span class="talk-topic__badge-name">{{ topic.category.name }}</span>
        </a>
      </div>
    </td>
    <td class="d-none d-md-table-cell text-center">{{ topic.posts_count }}</td>
    <td class="d-none d-md-table-cell text-center">{{ ago }}</td>
  </tr>
</template>

<style scoped lang="scss">
// develop's DiscourseTopic.vue: .topic { border: 0; background-color: white;
// padding: 10px; } plus a comic-card hover (border + offset box-shadow),
// paired with DashboardWhatsHappening.vue's border-spacing on the table so
// each row reads as its own card.
.talk-topic {
  background-color: #fff;
  padding: 10px;
  border: 0;

  &:hover {
    border: 1px solid #222;
    box-shadow:
      5px 5px 0 0,
      1px 1px 0 0 inset,
      -1px -1px 0 0 inset;
  }
}

.talk-topic__main {
  word-break: break-word;
  color: #222;

  > a {
    color: #222;
    text-decoration: none;

    &:hover {
      text-decoration: underline;
    }
  }
}

.talk-topic__badge {
  display: inline-flex;
  align-items: baseline;
  font-size: 0.87em;
  font-weight: bold;
  white-space: nowrap;
  text-decoration: none;
}

.talk-topic__badge-dot {
  flex: 0 0 auto;
  width: 9px;
  height: 9px;
  margin-right: 5px;
  display: inline-block;
}

.talk-topic__badge-name {
  color: #646464;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-size: 90%;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
