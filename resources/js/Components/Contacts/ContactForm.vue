<script setup>
import { useForm } from "@inertiajs/vue3";
import { Label } from "@/Components/UI/label/index.js";
import { Input } from "@/Components/UI/input/index.js";
import { Button } from "@/Components/UI/button/index.js";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/UI/select/index.js";
import { Card, CardContent } from "@/Components/UI/card/index.js";

const props = defineProps({
  contactable_type: { type: String, required: true },
  contactable_id: { type: [Number, String], required: true },
  contact: { type: Object, default: null },
  types: { type: Array, default: () => [] },
});

const emits = defineEmits(["saved", "cancel"]);

const form = useForm({
  model_type: props.contactable_type,
  model_id: props.contactable_id,
  type: props.contact?.type || props.types[0] || "",
  is_primary: props.contact?.is_primary || false,
  street_address: props.contact?.street_address || "",
  city: props.contact?.city || "",
  state: props.contact?.state || "",
  zip: props.contact?.zip || "",
  work_phone: props.contact?.work_phone || "",
  mobile_phone: props.contact?.mobile_phone || "",
});

const submit = () => {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      if (!props.contact) {
        form.reset(
          "type",
          "is_primary",
          "street_address",
          "city",
          "state",
          "zip",
          "work_phone",
          "mobile_phone",
        );
      }
      emits("saved");
    },
  };

  if (props.contact) {
    form.patch(route("contacts.update", props.contact.id), options);
  } else {
    form.post(route("contacts.store"), options);
  }
};
</script>

<template>
  <Card>
    <CardContent>
      <form
          class="space-y-3"
          @submit.prevent="submit"
      >
        <div class="grid gap-3 sm:grid-cols-2">
          <div class="space-y-2">
            <Label for="contact_type">Type</Label>
            <Select v-model="form.type">
              <SelectTrigger
                  id="contact_type"
                  class="w-full"
              >
                <SelectValue placeholder="Select a type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                    v-for="type in types"
                    :key="type"
                    :value="type"
                >
                  {{ type }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p
                v-if="form.errors.type"
                class="text-sm text-destructive"
            >
              {{ form.errors.type }}
            </p>
          </div>
          <label
              v-if="!props.contact"
              class="flex items-center gap-2 self-end pb-2 text-sm"
          >
            <input
                type="checkbox"
                v-model="form.is_primary"
            />
            Primary contact
          </label>
        </div>

        <div class="space-y-2">
          <Label for="contact_street">Street address</Label>
          <Input
              id="contact_street"
              type="text"
              v-model="form.street_address"
          />
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
          <div class="space-y-2">
            <Label for="contact_city">City</Label>
            <Input
                id="contact_city"
                type="text"
                v-model="form.city"
            />
          </div>
          <div class="space-y-2">
            <Label for="contact_state">State</Label>
            <Input
                id="contact_state"
                type="text"
                v-model="form.state"
            />
          </div>
          <div class="space-y-2">
            <Label for="contact_zip">Zip</Label>
            <Input
                id="contact_zip"
                type="text"
                v-model="form.zip"
            />
          </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
          <div class="space-y-2">
            <Label for="contact_work_phone">Work phone</Label>
            <Input
                id="contact_work_phone"
                type="text"
                v-model="form.work_phone"
            />
          </div>
          <div class="space-y-2">
            <Label for="contact_mobile_phone">Mobile phone</Label>
            <Input
                id="contact_mobile_phone"
                type="text"
                v-model="form.mobile_phone"
            />
          </div>
        </div>

        <div class="flex justify-end gap-3">
          <Button
              type="button"
              variant="ghost"
              size="sm"
              @click="emits('cancel')"
          >
            Cancel
          </Button>
          <Button
              type="submit"
              size="sm"
              :disabled="form.processing"
          >
            {{ contact ? 'Update Contact' : 'Save Contact' }}
          </Button>
        </div>
      </form>
    </CardContent>
  </Card>
</template>
