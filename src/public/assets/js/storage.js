export class Storage {
  static write(key, obj, persitent=false) {
    const objStr = JSON.stringify(obj);
    const driver = persitent ? localStorage : sessionStorage;
    driver.setItem(key, objStr);
  }

  static read(key, persitent=false) {
    const driver = persitent ? localStorage : sessionStorage;
    const item = driver.getItem(key);
    try {
      const storageObj = JSON.parse(item);
      return storageObj;
    } catch (err) {
      console.error(
`failed to parse data from storage.
${err}
deleting corrupted data.`
      );
      this.delete(key, persitent);
      return null;
    }
  }

  static delete(key, persitent=false) {
    const driver = persitent ? localStorage : sessionStorage;
    driver.removeItem(key);
  }

  static clear(persistent=false) {
    const driver = persistent ? localStorage : sessionStorage;
    driver.clear();
  }

  static clearAll() {
    this.clear(true);
    this.clear(false);
  }
};
