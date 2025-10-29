<template>
    <b-tr class="topic">
        <b-td class="main-link"><a :href="url">{{ topic.title }}</a>
            <div v-if="topic.category">
                <a class="badge-wrapper" :href="categoryUrl">
                    <span class="badge-category-bg"
                          v-bind:style="{ backgroundColor: '#'+topic.category.color}"
></span>
                    <span data-drop-close="true" class="badge-category clear-badge" :title="topic.category.title">
                        <span class="category-name">
                            {{ topic.category.name }}
                        </span>
                    </span>
                </a>
            </div>
        </b-td>
        <b-td class="d-none d-md-table-cell text-center">{{ topic.posts_count }}</b-td>
        <b-td class="d-none d-md-table-cell text-center">{{ ago }}</b-td>
    </b-tr>
</template>

<script>
import moment from 'moment'

export default {
  props: {
    topic: {
      type: Object,
      required: true
    },
    discourseBaseUrl: {
      type: String,
      required: true
    }
  },
  computed: {
    ago() {
      return new moment(this.topic.created_at).fromNow()
    },
    url() {
      return this.discourseBaseUrl + '/t/' + this.topic.slug
    },
    categoryUrl() {
      return this.discourseBaseUrl + this.topic.category.topic_url
    }
  }
}
</script>

<style scoped lang="scss">
.topic {
    border: 0;
    background-color: white;
    padding: 10px;
}

.table tr {
    border: 0 !important;

    &:hover {
        border: 1px solid #222;
        box-shadow: 5px 5px 0 0, 1px 1px 0 0 inset, -1px -1px 0 0 inset;
    }
}

.table td {
    border: 0 !important;
}

td.main-link {
    word-break: break-word;
    color: #222;

    a {
        text-decoration: none;

        &:hover {
            text-decoration: underline;
        }
    }
}



.badge-wrapper {
    font-size: .8706em;
    font-weight: bold;
    white-space: nowrap;
    position: relative;
    display: inline-flex;
    align-items: baseline;
}

.badge-category-bg {
    flex: 0 0 auto;
    width: 9px;
    height: 9px;
    margin-right: 5px;
    display: inline-block;
}

span.badge-category {
    color: #646464;
    overflow: hidden;
    text-overflow: ellipsis;
}

.badge-wrapper .badge-category .category-name {
    letter-spacing: 0.05em;
    text-transform: uppercase;
    font-size: 90%;
    text-overflow: ellipsis;
    overflow: hidden;
}
</style>
