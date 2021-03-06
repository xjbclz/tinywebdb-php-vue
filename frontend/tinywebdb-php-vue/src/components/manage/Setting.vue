<template>
  <BaseCard>
    <template #header>设置</template>

    <div v-if="loaded === true">
      <div class="setting-header">标签浏览页·分类列表</div>
      <b-form @submit.prevent="$refs.all_category_button.onClick()">
        <b-form-group>
          <b-input v-model="all_category.value" />
          <template #description>
            使用井号#分隔每一项（重复项会被隐藏）<br>
            例如：默认显示全部，可选显示“student_”或者“teacher_”开头的标签，则应当这么填：
            <b-link @click="all_category.value = '#student_#teacher_'">#student_#teacher_（点击预览）</b-link>
            <br>
            例如：默认显示“student_”，可选显示全部和开头为“teacher_”的标签，则应当这么填：
            <b-link @click="all_category.value = 'student_##teacher_'">student_##teacher_（点击预览）</b-link>
          </template>
        </b-form-group>
        <b-form-group label="预览">
          <b-select
              :value="all_category_preview[0].value"
              :options="all_category_preview"
              :select-size="Math.min(5, all_category_preview.length)" />
        </b-form-group>
        <SpinnerButton
            ref="all_category_button"
            :variant="typeof all_category.variant === 'string' ? all_category.variant : 'secondary'"
            @click="onDone => save('all_category', onDone)">
          <span v-text="typeof all_category.text === 'string' ? all_category.text : '保存'"/>
        </SpinnerButton>
      </b-form>

      <hr>

      <div v-for="(settingConfig, settingId) in pluginSettings" :key="settingId">
        <SettingGroup :ref="settingId" :id="settingId" :config="settingConfig" @submit="newSave" />
        <hr />
      </div>

      <div class="setting-header">危险选项</div>
      <div>
        <b-form-group>
          <SpinnerButton @click="eraseData" variant="danger">清除数据库</SpinnerButton>
          <span style="margin-left:10px" class="vertical-auto-margin text-danger">执行清空数据库，设置将被保留</span>
        </b-form-group>
        <b-form-group>
          <SpinnerButton @click="erasePwd" variant="danger">清除登录密码</SpinnerButton>
          <span style="margin-left:10px" class="vertical-auto-margin text-danger">清除设置将会重置已有密码，请及时设置新密码</span>
        </b-form-group>
        <b-form-group>
          <SpinnerButton @click="eraseAll" variant="danger">清除数据库和所有设置</SpinnerButton>
          <span style="margin-left:10px" class="vertical-auto-margin text-danger">完全重置TPV</span>
        </b-form-group>
      </div>
    </div>
    <div v-else>
      <div v-if="loaded === false">
        <b-spinner />
        设置加载中
      </div>
      <div v-else>
        <span v-text="loaded" />
        <b-button @click="loadSettings">重试</b-button>
      </div>
    </div>
  </BaseCard>
</template>

<script>
import SettingGroup from '@/components/manage/functional/SettingGroup.vue'

export default {
  name: 'ManageSetting',
  components: { SettingGroup },
  data () {
    return {
      loaded: false,
      all_category: { value: '', variant: undefined, text: undefined }
    }
  },
  computed: {
    all_category_preview () {
      return Array.from(new Set(this.all_category.value.split('#')))
        .map(item => ({value: item, text: item.length === 0 ? '显示所有' : `前缀\`${item}\``}))
    },
    pluginSettings () {
      let rtn = {}
      for (let plugin in this.$root.plugins) {
        for (let setting in this.$root.plugins[plugin].settings) {
          rtn[plugin + '_' + setting] = { plugin, ...this.$root.plugins[plugin].settings[setting] }
        }
      }
      return rtn
    }
  },
  created () {
    this.loadSettings()
  },
  methods: {
    async loadSettings () {
      this.loaded = false
      let { status, result } = (await this.$parent.service.get('settings')).data
      switch (status) {
        case 0: {
          this.loaded = true
          this.all_category.value = result.all_category || ''
          this.$nextTick(() => {
            for (let settingName in this.$refs) {
              if (Array.isArray(this.$refs[settingName])) {
                this.$refs[settingName][0].setValue(result[settingName] || undefined)
              }
            }
          })
          break
        }
        default: {
          this.loaded = '拉取设置失败'
        }
      }
    },
    save (settingId, onDone = () => {}) {
      this.newSave(settingId, (this[settingId] || {}).value, this[settingId] || {}, onDone)
    },
    async newSave (settingId, value, submitButton, onDone = () => {}) {
      if (value === undefined || value === null) {
        this.$root.showInfo('设置内容为undefined或null，请刷新页面后重试。如反复出现请联系作者排查问题')
        return
      }
      value = Array.isArray(value) || typeof value === 'object' ? JSON.stringify(value) : value
      let { status } = (await this.$parent.service.post('setting_update', { settingId, value })).data
      onDone()
      switch (status) {
        case 0: {
          submitButton.text = '保存成功'
          submitButton.variant = 'success'
          await this.$root.sleep(1000)
          submitButton.text = undefined
          submitButton.variant = undefined
          break
        }
        case 50: {
          this.$root.showInfo(`保存设置'${settingId}'失败，服务器无法识别该设置项，可能是由于服务器版本过老引起的`)
          break
        }
        default: {
          this.$root.showInfo(`保存设置'${settingId}'失败，错误码${status}`)
        }
      }
    },

    eraseData (onDone) {
      this.$root.showConfirm('', '确认要清空数据库吗？该操作无法逆转！请提前做好数据备份', async () => {
        let { status } = (await this.$parent.service.post('erase_data')).data
        switch (status) {
          case 0: {
            this.$root.showInfo('数据已清空')
            break
          }
          default: {
            this.$root.showInfo(`重置密码失败，错误码${status}`)
          }
        }
        onDone()
      }, onDone)
    },
    erasePwd (onDone) {
      this.$root.showConfirm('', '确认要清除登录密码？', async () => {
        let { status } = (await this.$parent.service.post('erase_pwd')).data
        switch (status) {
          case 0: {
            this.$parent.token = null
            this.$router.push('/manage/init')
            this.$root.showInfo('系统密码已重置，请重新设置新的密码')
            break
          }
          default: {
            this.$root.showInfo(`重置密码失败，错误码${status}`)
          }
        }
        onDone()
      }, onDone)
    },
    eraseAll (onDone) {
      this.$root.showConfirm('', '确认要清空数据库并且清除所有设置（包括后台密码）吗？该操作无法逆转！请提前做好数据备份', async () => {
        let { status } = (await this.$parent.service.post('erase_all')).data
        switch (status) {
          case 0: {
            this.$parent.token = null
            this.$router.push('/manage/init')
            this.$root.showInfo('数据库和所有设置已清除')
            break
          }
          default: {
            this.$root.showInfo(`清除失败，错误码${status}`)
          }
        }
        onDone()
      }, onDone)
    }
  }
}
</script>

<style>

</style>
