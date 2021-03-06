<template>
  <BaseCard>
    <template #header>
      <div>
        <b-input-group>
          <div class="vertical-auto-margin">标签 -</div>
          <b-select plain style="padding:0" v-model="currentCategory" :options="categories" />
        </b-input-group>
      </div>
      <div style="display:flex">
        <div>
          <b-input-group>
            <div class="vertical-auto-margin">一页显示数量</div>
            <b-select plain style="padding:0" v-model="perPage" :options="[ 5, 10, 20, 50, 100 ]" />
            <b-link class="vertical-auto-margin" @click="loadItems">刷新</b-link>
          </b-input-group>
        </div>
        <div style="margin:-4px -8px -4px 10px">
          <b-input-group>
            <b-input
                ref="searchBox"
                size="sm"
                v-show="search.show"
                v-model="search.text"
                placeholder="在标签中搜索"
                @keypress.enter="onSearch()" />
            <b-button size="sm" @click="onSearch">
              <font-awesome-icon icon="search" />
            </b-button>
          </b-input-group>
        </div>
      </div>
    </template>

    <DataTable
        :item-count="itemCount"
        :current-page="currentPage"
        :busy="isLoading"
        :items="items"
        :per-page="perPage"
        @switchPage="onSwitchPage"
        @requestLoadItems="loadItems" />
  </BaseCard>
</template>

<script>
import DataTable from '@/components/manage/functional/DataTable.vue'

import qs from 'qs'

export default {
  name: 'ManageAll',
  components: { DataTable },
  data () {
    return {
      currentCategory: '',
      categories: [ { value: '', text: '显示所有' } ],
      perPage: 20,
      currentPage: 1,
      isLoading: false,
      items: [],
      itemCount: 0,
      search: {
        text: '',
        show: false
      }
    }
  },
  watch: {
    '$route.query' () {
      let queryPage = Number.parseInt(this.$route.query.page)
      if (queryPage !== this.currentPage && !Number.isNaN(queryPage)) {
        this.currentPage = queryPage
        this.loadItems()
      }
    },
    currentCategory () {
      this.loadItems()
    },
    currentPage (val) {
      this.$router.push('?page=' + val)
    },
    perPage () {
      this.currentPage = 1
      this.loadItems()
    }
  },
  async mounted () {
    this.isLoading = true

    let { status, result } = (await this.$parent.service.get('settings')).data
    switch (status) {
      case 0: {
        this.categories = Array.from(
          new Set((result.all_category || '').split('#')))
          .map(value => value === '' ? ({ value: '', text: '显示所有' }) : { value, text: `前缀\`${value}\`` })
        break
      }
      default: {
        this.categories = [ { value: '', text: '显示所有' } ]
        this.$root.showInfo(`拉取分类失败，错误码${status}`)
      }
    }
    this.currentCategory = this.categories[0].value

    let queryPage = Number.parseInt(this.$route.query.page)
    if (queryPage > 1) {
      // ignore NaN(from undefined) or 1-
      this.currentPage = queryPage
    }
    this.loadItems()
  },
  methods: {
    async loadItems () {
      this.isLoading = true
      let { status, result } = (await this.$parent.service.get('page', {
        params: {
          page: this.currentPage,
          perPage: this.perPage,
          prefix: this.currentCategory,
          valueLengthLimit: 200
        }
      })).data
      this.isLoading = false
      switch (status) {
        case 0: {
          this.itemCount = result.count
          this.items = result.content.map(item => ({ selected: false, deleted: false, ...item }))
          this.currentPage = result.actualPage
          return
        }
        default: {
          this.$root.showInfo(`数据加载失败，错误码：${status}`)
        }
      }
    },
    onSwitchPage (to) {
      if (to === this.currentPage) {
        return
      }
      this.currentPage = to
      this.loadItems()
    },
    onSearch () {
      if (this.search.show === true) {
        this.$router.push({ path: '/manage/search?' + qs.stringify({ text: this.search.text }) })
      } else {
        this.search.show = true
        this.$nextTick(() => this.$refs.searchBox.focus())
      }
    }
  }
}
</script>

<style>

</style>
