import * as SecureStore from 'expo-secure-store'

/** Small key/value storage on the phone (encrypted). Failures are ignored: the app still works, it just forgets. */
export const storage = {
  get: (key: string) => SecureStore.getItemAsync(key).catch(() => null),
  set: (key: string, value: string) => SecureStore.setItemAsync(key, value).catch(() => {}),
  remove: (key: string) => SecureStore.deleteItemAsync(key).catch(() => {}),
}
