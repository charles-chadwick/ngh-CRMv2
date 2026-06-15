<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { TrashIcon } from '@heroicons/vue/24/outline'
import Avatar from '@/Components/Avatar.vue'
import { Button } from '@/Components/UI/button/index.js'
import { Label } from '@/Components/UI/label/index.js'

const props = defineProps({
  model_type: {
    type: String,
    required: true,
  },
  model_id: {
    type: [Number, String],
    required: true,
  },
  current_image: {
    type: String,
    default: null,
  },
  name: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: 'Avatar',
  },
})

const file_input = ref(null)
const uploading = ref(false)
const removing = ref(false)
const preview_url = ref(props.current_image)

watch(() => props.current_image, (value) => {
  preview_url.value = value
})

const onFileChange = (event) => {
  const file = event.target.files[0]

  if (file) {
    preview_url.value = URL.createObjectURL(file)
    uploadFile(file)
  }
}

const uploadFile = (file) => {
  uploading.value = true

  router.post(route('avatar.store'), {
    model_type: props.model_type,
    model_id: props.model_id,
    image: file,
  }, {
    preserveScroll: true,
    onFinish: () => {
      uploading.value = false

      if (file_input.value) {
        file_input.value.value = ''
      }
    },
    onError: () => {
      preview_url.value = props.current_image
    },
  })
}

const removeImage = () => {
  removing.value = true

  router.delete(route('avatar.destroy'), {
    data: {
      model_type: props.model_type,
      model_id: props.model_id,
    },
    preserveScroll: true,
    onSuccess: () => {
      preview_url.value = null
    },
    onFinish: () => {
      removing.value = false
    },
  })
}
</script>

<template>
  <div class="flex items-start gap-6">
    <Avatar
        :src="preview_url"
        :name="name"
    />

    <div class="flex-1 space-y-2">
      <Label>{{ label }}</Label>

      <div class="flex items-center gap-3">
        <Button
            type="button"
            variant="outline"
            size="sm"
            :disabled="uploading"
            @click="file_input?.click()"
        >
          {{ uploading ? 'Uploading…' : 'Upload' }}
        </Button>

        <Button
            v-if="preview_url"
            type="button"
            variant="ghost"
            size="icon-sm"
            class="text-destructive hover:text-destructive"
            :disabled="removing || uploading"
            @click="removeImage"
        >
          <TrashIcon class="size-4" />
        </Button>

        <input
            ref="file_input"
            type="file"
            accept="image/jpeg,image/png,image/gif,image/webp"
            class="sr-only"
            :disabled="uploading"
            @change="onFileChange"
        />
      </div>

      <p class="text-sm text-muted-foreground">
        JPG, PNG, GIF or WebP. Max 2MB.
      </p>
    </div>
  </div>
</template>
